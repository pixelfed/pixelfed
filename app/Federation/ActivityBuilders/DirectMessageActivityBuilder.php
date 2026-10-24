<?php

namespace App\Federation\ActivityBuilders;

use App\Models\DmConversation;
use App\Models\DmMessage;
use App\Models\Media;
use App\Models\Profile;
use App\Services\DirectMessagePayloadService;
use Illuminate\Support\Collection;

class DirectMessageActivityBuilder
{
    public const CONTEXT = [
        'https://www.w3.org/ns/activitystreams',
        [
            'ostatus' => 'http://ostatus.org#',
            'conversation' => 'ostatus:conversation',
            'sensitive' => 'as:sensitive',
            'toot' => 'http://joinmastodon.org/ns#',
            'blurhash' => 'toot:blurhash',
        ],
    ];

    /**
     * @param  Collection<int, Profile>  $others  Everyone in the conversation except the sender
     * @return array<string, mixed>
     */
    public function buildCreate(DmMessage $message, DmConversation $conversation, Profile $sender, Collection $others): array
    {
        $note = $this->buildNote($message, $conversation, $sender, $others);

        return [
            '@context' => self::CONTEXT,
            'id' => $note['id'].'/activity',
            'type' => 'Create',
            'actor' => $note['attributedTo'],
            'published' => $note['published'],
            'to' => $note['to'],
            'cc' => [],
            'object' => $note,
        ];
    }

    /**
     * Three things make this thread on Mastodon and show up under
     * Conversations rather than as a stray private post:
     *
     * - every addressee in `to` also has a Mention tag, otherwise the status
     *   is filed as "limited" and never reaches the DM view
     * - `inReplyTo` points at the previous message, so the receiving server
     *   puts it in the same conversation as its parent
     * - `context` and `conversation` repeat what the thread is known by, for
     *   servers that never saw the parent
     *
     * @param  Collection<int, Profile>  $others
     * @return array<string, mixed>
     */
    public function buildNote(DmMessage $message, DmConversation $conversation, Profile $sender, Collection $others): array
    {
        $recipients = $others->filter(fn (Profile $profile) => $profile->status === null)->values();
        $uri = $message->objectUri();

        return [
            'id' => $uri,
            'type' => 'Note',
            'summary' => null,
            'content' => DirectMessagePayloadService::renderHtml($message->body),
            'inReplyTo' => $this->inReplyTo($message),
            'published' => $message->created_at->toAtomString(),
            'url' => $uri,
            'attributedTo' => $sender->permalink(),
            'to' => $recipients->map(fn (Profile $profile) => $profile->permalink())->values()->all(),
            'cc' => [],
            'sensitive' => (bool) $message->is_sensitive,
            'context' => $conversation->contextUri(),
            'conversation' => $conversation->conversationUri(),
            'attachment' => $message->media()->get()->map(fn (Media $media) => $this->attachment($media))->values()->all(),
            'tag' => $recipients->map(fn (Profile $profile) => [
                'type' => 'Mention',
                'href' => $profile->permalink(),
                'name' => $this->mentionName($profile),
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Profile>  $others
     * @return array<string, mixed>
     */
    public function buildDelete(DmMessage $message, Profile $sender, Collection $others): array
    {
        $uri = $message->objectUri();

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $uri.'#delete',
            'type' => 'Delete',
            'actor' => $sender->permalink(),
            'to' => $others
                ->filter(fn (Profile $profile) => $profile->status === null)
                ->map(fn (Profile $profile) => $profile->permalink())
                ->values()
                ->all(),
            'object' => [
                'id' => $uri,
                'type' => 'Tombstone',
            ],
        ];
    }

    /**
     * The message being answered, or failing that the one right before this
     * one, so the thread is a chain remote servers can follow.
     */
    protected function inReplyTo(DmMessage $message): ?string
    {
        if ($message->in_reply_to_id) {
            $parent = DmMessage::find($message->in_reply_to_id);

            if ($parent) {
                return $parent->objectUri();
            }
        }

        $previous = DmMessage::where('conversation_id', $message->conversation_id)
            ->where('id', '<', $message->id)
            ->whereNotIn('type', [DmMessage::TYPE_STORY_REACT, DmMessage::TYPE_STORY_COMMENT])
            ->orderByDesc('id')
            ->first();

        return $previous?->objectUri();
    }

    /**
     * @return array<string, mixed>
     */
    protected function attachment(Media $media): array
    {
        $attachment = [
            'type' => $media->activityVerb(),
            'mediaType' => $media->mime === 'image/jpg' ? 'image/jpeg' : $media->mime,
            'url' => $media->url(),
            'name' => $media->caption,
        ];

        if ($media->blurhash) {
            $attachment['blurhash'] = $media->blurhash;
        }

        if ($media->width && $media->height) {
            $attachment['width'] = (int) $media->width;
            $attachment['height'] = (int) $media->height;
        }

        return $attachment;
    }

    protected function mentionName(Profile $profile): string
    {
        if ($profile->domain === null) {
            return '@'.$profile->username.'@'.parse_url(config('app.url'), PHP_URL_HOST);
        }

        return str_starts_with($profile->username, '@') ? $profile->username : '@'.$profile->username;
    }
}
