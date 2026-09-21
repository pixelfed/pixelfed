<?php

namespace App\Services;

use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Transformer\Api\MediaTransformer;
use App\Util\Lexer\Autolink;
use Illuminate\Support\Collection;

class DirectMessagePayloadService
{
    /*
    |--------------------------------------------------------------------------
    | Conversations
    |--------------------------------------------------------------------------
    */

    /**
     * @param  Collection<int, DmConversationParticipant>  $rows  The viewer's participant rows, with `conversation` loaded
     * @return array<int, array<string, mixed>>
     */
    public function conversations(Collection $rows, int $viewerId): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $conversationIds = $rows->pluck('conversation_id')->all();

        $members = DmConversationParticipant::whereIn('conversation_id', $conversationIds)
            ->orderBy('id')
            ->get()
            ->groupBy('conversation_id');

        $lastMessages = DmMessage::with('media')
            ->whereIn('id', $rows->map(fn ($row) => $row->conversation?->last_message_id)->filter()->all())
            ->get()
            ->keyBy('id');

        $blocked = $this->blockedIds($viewerId);

        return $rows
            ->filter(fn ($row) => $row->conversation !== null)
            ->map(function (DmConversationParticipant $row) use ($members, $lastMessages, $viewerId, $blocked) {
                $last = $lastMessages->get($row->conversation->last_message_id);

                if ($last && in_array((int) $last->profile_id, $blocked, true)) {
                    $last = null;
                }

                return $this->conversation(
                    $row->conversation,
                    $row,
                    $members->get($row->conversation_id, collect()),
                    $last,
                    $viewerId
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, DmConversationParticipant>|null  $members
     * @return array<string, mixed>
     */
    public function conversation(DmConversation $conversation, DmConversationParticipant $viewer, ?Collection $members, ?DmMessage $last, int $viewerId): array
    {
        $members ??= DmConversationParticipant::where('conversation_id', $conversation->id)->orderBy('id')->get();

        $participants = $members
            ->filter(fn ($member) => (int) $member->profile_id !== $viewerId)
            ->map(fn ($member) => AccountService::get($member->profile_id, true))
            ->filter()
            ->values()
            ->all();

        return [
            'id' => (string) $conversation->id,
            'type' => $conversation->type,
            'name' => $conversation->name,
            'participants' => $participants,
            'participant_count' => $members->count(),
            'state' => $viewer->state,
            'unread_count' => (int) $viewer->unread_count,
            'muted' => $viewer->muted_at !== null,
            'hidden' => $viewer->hidden_at !== null,
            'last_read_message_id' => $viewer->last_read_message_id ? (string) $viewer->last_read_message_id : null,
            'last_message' => $last ? $this->message($last, $viewerId) : null,
            'created_at' => $this->timestamp($conversation->created_at),
            'updated_at' => $this->timestamp($viewer->last_activity_at ?? $conversation->last_message_at ?? $conversation->updated_at),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    /**
     * Text and media always travel together. `type` is only a hint for which
     * bubble to draw.
     *
     * @return array<string, mixed>
     */
    public function message(DmMessage $message, int $viewerId): array
    {
        return [
            'id' => (string) $message->id,
            'conversation_id' => (string) $message->conversation_id,
            'sender_id' => (string) $message->profile_id,
            'is_author' => (int) $message->profile_id === $viewerId,
            'type' => $message->type,
            'text' => $message->body,
            'media' => $this->media($message),
            'meta' => $message->meta,
            'sensitive' => (bool) $message->is_sensitive,
            'in_reply_to_id' => $message->in_reply_to_id ? (string) $message->in_reply_to_id : null,
            'edited_at' => $this->timestamp($message->edited_at),
            'created_at' => $this->timestamp($message->created_at),
        ];
    }

    /**
     * @param  Collection<int, DmMessage>  $messages
     * @return array<int, array<string, mixed>>
     */
    public function messages(Collection $messages, int $viewerId): array
    {
        return $messages->map(fn (DmMessage $message) => $this->message($message, $viewerId))->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function media(DmMessage $message): array
    {
        $media = $message->relationLoaded('media') ? $message->media : $message->media()->get();

        if ($media->isEmpty()) {
            return [];
        }

        return FractalService::collection($media, new MediaTransformer);
    }

    /**
     * The shape the `/direct/thread` endpoints have always returned.
     *
     * @return array<string, mixed>
     */
    public function legacyMessage(DmMessage $message, int $viewerId, bool $hidden = false, ?int $otherLastReadId = null, ?int $viewerLastReadId = null): array
    {
        $media = $this->media($message);
        $isAuthor = (int) $message->profile_id === $viewerId;
        $readMarker = $isAuthor ? $otherLastReadId : $viewerLastReadId;

        return [
            'id' => (string) $message->id,
            'hidden' => $hidden,
            'isAuthor' => $isAuthor,
            'type' => $message->type,
            'text' => $message->body,
            'media' => $media[0]['url'] ?? null,
            'carousel' => $media,
            'created_at' => $message->created_at->format('c'),
            'timeAgo' => $message->created_at->diffForHumans(null, null, true),
            'seen' => $readMarker !== null && $readMarker >= $message->id,
            'reportId' => (string) $message->id,
            'meta' => $message->meta,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Mastodon compatible
    |--------------------------------------------------------------------------
    */

    /**
     * @param  Collection<int, DmConversationParticipant>  $members
     * @return array<string, mixed>|null
     */
    public function mastodonConversation(DmConversation $conversation, DmConversationParticipant $viewer, Collection $members, ?DmMessage $last, int $viewerId): ?array
    {
        $accounts = $members
            ->filter(fn ($member) => (int) $member->profile_id !== $viewerId)
            ->map(fn ($member) => AccountService::getMastodon($member->profile_id, true))
            ->filter(fn ($account) => $account && isset($account['id']))
            ->values()
            ->all();

        if (empty($accounts) || ! $last) {
            return null;
        }

        return [
            'id' => (string) $conversation->id,
            'unread' => $viewer->unread_count > 0,
            'accounts' => $accounts,
            'last_status' => $this->mastodonStatus($last, $accounts, $viewerId),
        ];
    }

    /**
     * Messages are not statuses any more, but Mastodon clients expect one as
     * `last_status`, so this builds the entity from the message.
     *
     * @param  array<int, array<string, mixed>>  $accounts
     * @return array<string, mixed>
     */
    public function mastodonStatus(DmMessage $message, array $accounts, int $viewerId): array
    {
        $media = collect($this->media($message))->map(function (array $item) {
            $mime = $item['mime'] ?? null;

            unset(
                $item['optimized_url'],
                $item['license'],
                $item['is_nsfw'],
                $item['orientation'],
                $item['filter_name'],
                $item['filter_class'],
                $item['mime'],
                $item['hls_manifest']
            );

            $item['type'] = $mime ? strtolower(explode('/', $mime)[0]) : 'unknown';

            return $item;
        })->values()->all();

        $uri = $message->objectUri();

        return [
            'id' => (string) $message->id,
            'uri' => $uri,
            'url' => $uri,
            'in_reply_to_id' => $message->in_reply_to_id ? (string) $message->in_reply_to_id : null,
            'in_reply_to_account_id' => null,
            'reblog' => null,
            'content' => self::renderHtml($message->body),
            'content_text' => $message->body,
            'created_at' => $this->timestamp($message->created_at),
            'edited_at' => $this->timestamp($message->edited_at),
            'emojis' => [],
            'replies_count' => 0,
            'reblogs_count' => 0,
            'favourites_count' => 0,
            'reblogged' => false,
            'favourited' => false,
            'muted' => false,
            'bookmarked' => false,
            'sensitive' => (bool) $message->is_sensitive,
            'spoiler_text' => '',
            'visibility' => 'direct',
            'application' => null,
            'language' => null,
            'mentions' => collect($accounts)
                ->filter(fn ($account) => (string) $account['id'] !== (string) $message->profile_id)
                ->map(fn ($account) => [
                    'id' => (string) $account['id'],
                    'username' => $account['username'] ?? null,
                    'acct' => $account['acct'] ?? null,
                    'url' => $account['url'] ?? null,
                ])
                ->values()
                ->all(),
            'tags' => [],
            'card' => null,
            'poll' => null,
            'pf_type' => $message->type,
            'media_attachments' => $media,
            'account' => AccountService::getMastodon($message->profile_id, true),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Bodies are stored as plain text. Escape first, then link, so nothing a
     * person typed can turn into markup here or on a remote server.
     */
    public static function renderHtml(?string $body): string
    {
        if ($body === null || $body === '') {
            return '';
        }

        $escaped = htmlspecialchars($body, ENT_NOQUOTES, 'UTF-8');

        return '<p>'.nl2br(Autolink::create()->autolink($escaped), false).'</p>';
    }

    /**
     * @return array<int, int>
     */
    public function blockedIds(int $viewerId): array
    {
        return array_map('intval', UserFilterService::blocks($viewerId) ?: []);
    }

    protected function timestamp($value): ?string
    {
        if (! $value) {
            return null;
        }

        return str_replace('+00:00', 'Z', $value->format(DATE_RFC3339_EXTENDED));
    }
}
