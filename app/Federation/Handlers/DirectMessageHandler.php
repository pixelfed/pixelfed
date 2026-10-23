<?php

namespace App\Federation\Handlers;

use App\Exceptions\DirectMessageException;
use App\Federation\Validators\DirectMessageValidator;
use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Services\DirectMessageService;
use App\Services\FollowersSyncService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Stevebauman\Purify\Facades\Purify;
use Throwable;

class DirectMessageHandler
{
    public const MAX_BODY_LENGTH = 5000;

    public function __construct(protected DirectMessageService $service) {}

    /**
     * Store a direct Note delivered by $actor.
     *
     * The conversation is whoever the Note is addressed to plus its author,
     * so one recipient is a 1:1 chat and several are a group. Returns null
     * when the message is dropped, which is never an error: the sender is
     * not told about blocks, privacy settings or limits.
     */
    public function handleCreate(array $object, Profile $actor): ?DmMessage
    {
        if ($actor->domain === null || $actor->status !== null) {
            return null;
        }

        if (! DirectMessageValidator::validate($object)) {
            return null;
        }

        $id = Helpers::pluckval($object['id']);

        if ($this->alreadyStored($id)) {
            return null;
        }

        $others = $this->resolveParticipants($object, $actor);

        if ($others === null) {
            return null;
        }

        $locals = $others->filter(fn (Profile $profile) => $profile->domain === null);

        // A recipient who blocks the sender never sees the message. When that
        // leaves nobody here to read it there is nothing to store.
        $readers = $locals->reject(fn (Profile $profile) => $this->service->isBlockedBy($profile, $actor));

        if ($readers->isEmpty()) {
            return null;
        }

        $existing = DmConversation::where(
            'participants_hash',
            DmConversation::participantsHash($others->pluck('id')->push($actor->id)->all())
        )->first();

        if ($existing) {
            // Someone who left the conversation is no longer reading it
            $left = DmConversationParticipant::where('conversation_id', $existing->id)
                ->where('state', DmConversationParticipant::STATE_LEFT)
                ->pluck('profile_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $readers = $readers->reject(fn (Profile $profile) => in_array((int) $profile->id, $left, true));

            if ($readers->isEmpty() || $this->requestLimitReached($existing, $actor, $readers)) {
                return null;
            }
        }

        $body = self::plainText($object['content'] ?? null);
        $media = $this->storeAttachments($object, $actor);

        if ($body === null && $media->isEmpty()) {
            return null;
        }

        try {
            $conversation = $existing ?? $this->service->findOrCreateConversation($actor, $others, [
                'context_uri' => $this->uri($object['context'] ?? null),
                'conversation_uri' => $this->uri($object['conversation'] ?? null),
            ]);
        } catch (DirectMessageException) {
            return null;
        }

        $this->service->adoptContext(
            $conversation,
            $this->uri($object['context'] ?? null),
            $this->uri($object['conversation'] ?? null)
        );

        try {
            return $this->service->storeMessage($conversation, $actor, [
                'body' => $body,
                'media' => $media,
                'ap_object_uri' => $id,
                'in_reply_to_id' => $this->parentId($conversation, $object['inReplyTo'] ?? null),
                'is_sensitive' => (bool) ($object['sensitive'] ?? false),
            ]);
        } catch (Throwable $e) {
            // Most likely the same delivery processed twice at once
            Log::info('DirectMessageHandler: message not stored', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * An Update for a message $actor wrote: the text changed. Returns true
     * when the object was a direct message.
     */
    public function handleUpdate(array $object, Profile $actor): bool
    {
        $id = Helpers::pluckval($object['id'] ?? null);

        if (! is_string($id)) {
            return false;
        }

        $message = DmMessage::whereObjectUri($id)->first();

        if (! $message) {
            return false;
        }

        if ((int) $message->profile_id !== (int) $actor->id || ! DirectMessageValidator::isDirect($object, $actor)) {
            return true;
        }

        if (isset($object['content']) && is_string($object['content'])) {
            $body = self::plainText($object['content']);

            if ($body !== $message->body) {
                $message->body = $body;
                $message->edited_at = now();
                $message->save();
            }
        }

        return true;
    }

    /**
     * A Delete for a message $actor wrote. Returns true when the object was a
     * direct message, so the caller can stop looking for a status.
     */
    public function handleDelete(Profile $actor, string $objectId): bool
    {
        $message = DmMessage::whereObjectUri($objectId)->first();

        if (! $message) {
            return false;
        }

        if ((int) $message->profile_id !== (int) $actor->id) {
            return true;
        }

        $this->service->deleteMessage($message, false);

        return $message->status_id === null;
    }

    /**
     * Messages from before the refactor live in the statuses table, and a
     * redelivery of one of those must not come back as a new message.
     */
    protected function alreadyStored(string $id): bool
    {
        if (DmMessage::withTrashed()->whereObjectUri($id)->exists()) {
            return true;
        }

        return Status::withTrashed()->where('uri', $id)->exists()
            || Status::withTrashed()->where('object_url', $id)->exists();
    }

    /**
     * Everyone the Note is addressed to, other than its author. Null means
     * the message cannot be a conversation here: nobody local is addressed,
     * or more people than a group allows.
     *
     * Addressees that do not resolve to an actor are left out. Everyone who
     * does resolve was named by the sender, so the message still only reaches
     * people it was meant for.
     *
     * @return Collection<int, Profile>|null
     */
    protected function resolveParticipants(array $object, Profile $actor): ?Collection
    {
        $audience = array_values(array_filter(
            DirectMessageValidator::audience($object),
            fn (string $uri) => $uri !== $actor->remote_url
        ));

        if (empty($audience)) {
            return null;
        }

        if (count($audience) + 1 > (int) config('dm.groups.max_participants')) {
            return null;
        }

        if (count($audience) > 1 && ! config('dm.groups.enabled')) {
            return null;
        }

        $fetches = 0;
        $maxFetches = (int) config('dm.federation.max_actor_fetches');
        $resolved = collect();

        foreach ($audience as $uri) {
            $host = parse_url($uri, PHP_URL_HOST);

            if (! is_string($host)) {
                continue;
            }

            if (Helpers::isLocalDomain($host)) {
                $local = FollowersSyncService::resolveLocalActor($uri);
                $profile = $local ? Profile::find($local->id) : null;

                if ($profile && $profile->status === null && $profile->user_id) {
                    $resolved->put($profile->id, $profile);
                }

                continue;
            }

            $profile = Profile::whereRemoteUrl($uri)->first();

            if (! $profile && $fetches < $maxFetches && Helpers::validateUrl($uri)) {
                $fetches++;

                try {
                    $profile = Helpers::profileFetch($uri);
                } catch (Throwable) {
                    $profile = null;
                }
            }

            if ($profile && $profile->status === null && $profile->id !== $actor->id) {
                $resolved->put($profile->id, $profile);
            }
        }

        if ($resolved->filter(fn (Profile $profile) => $profile->domain === null)->isEmpty()) {
            return null;
        }

        return $resolved->values();
    }

    /**
     * While every reader still has the conversation as a request, only so
     * many messages from this sender are kept.
     *
     * @param  Collection<int, Profile>  $readers
     */
    protected function requestLimitReached(DmConversation $conversation, Profile $actor, Collection $readers): bool
    {
        $accepted = DmConversationParticipant::where('conversation_id', $conversation->id)
            ->whereIn('profile_id', $readers->pluck('id'))
            ->where('state', DmConversationParticipant::STATE_ACTIVE)
            ->exists();

        if ($accepted) {
            return false;
        }

        $stored = DmMessage::where('conversation_id', $conversation->id)
            ->where('profile_id', $actor->id)
            ->count();

        return $stored >= (int) config('dm.requests.inbound_limit');
    }

    /**
     * @return Collection<int, Media>
     */
    protected function storeAttachments(array $object, Profile $actor): Collection
    {
        $attachments = $object['attachment'] ?? [];

        if (! is_array($attachments) || empty($attachments)) {
            return collect();
        }

        if (! array_is_list($attachments)) {
            $attachments = [$attachments];
        }

        $allowed = explode(',', (string) config_cache('pixelfed.media_types'));
        $stored = collect();

        foreach (array_slice($attachments, 0, (int) config('dm.max_media')) as $attachment) {
            if (! is_array($attachment)) {
                continue;
            }

            $mime = $attachment['mediaType'] ?? null;
            $url = $attachment['url'] ?? null;

            if (is_array($url)) {
                $url = $url['href'] ?? ($url[0]['href'] ?? null);
            }

            if (! is_string($mime) || ! in_array($mime, $allowed, true)) {
                continue;
            }

            if (! is_string($url) || strlen($url) > 255 || ! Helpers::validateUrl($url)) {
                continue;
            }

            $media = new Media;
            $media->remote_media = true;
            $media->status_id = null;
            $media->profile_id = $actor->id;
            $media->user_id = null;
            $media->media_path = $url;
            $media->remote_url = $url;
            $media->mime = $mime;
            $media->version = 3;
            $media->order = $stored->count() + 1;
            $media->is_nsfw = (bool) ($object['sensitive'] ?? false);
            $media->blurhash = is_string($attachment['blurhash'] ?? null) ? $attachment['blurhash'] : null;
            $media->caption = is_string($attachment['name'] ?? null)
                ? mb_substr(Purify::clean($attachment['name']), 0, 1000)
                : null;

            if (is_numeric($attachment['width'] ?? null) && is_numeric($attachment['height'] ?? null)) {
                $media->width = (int) $attachment['width'];
                $media->height = (int) $attachment['height'];
            }

            try {
                $media->save();
            } catch (Throwable) {
                continue;
            }

            $stored->push($media);
        }

        return $stored;
    }

    protected function parentId(DmConversation $conversation, mixed $inReplyTo): ?int
    {
        $uri = $this->uri($inReplyTo);

        if (! $uri) {
            return null;
        }

        $id = DmMessage::whereObjectUri($uri)
            ->where('conversation_id', $conversation->id)
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * A property that should be an id: a string, or an object carrying one.
     */
    protected function uri(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value['id'] ?? (array_is_list($value) ? ($value[0] ?? null) : null);
        }

        if (! is_string($value) || $value === '' || strlen($value) > 1024) {
            return null;
        }

        return $value;
    }

    /**
     * Messages are kept as plain text. Paragraphs and line breaks survive,
     * markup does not, and the mentions Mastodon puts in front of every
     * direct message are dropped because the participants already say who it
     * is for.
     */
    public static function plainText(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $html);

        $text = strip_tags(Purify::clean($html));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        $text = preg_replace('/^\s*(?:@[\p{L}\p{N}_.\-]+(?:@[\p{L}\p{N}_.\-]+)?[\s,:]*)+/u', '', $text);

        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/ ?\n ?/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, self::MAX_BODY_LENGTH);
    }
}
