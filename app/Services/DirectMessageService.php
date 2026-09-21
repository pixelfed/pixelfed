<?php

namespace App\Services;

use App\Exceptions\DirectMessageException;
use App\Federation\ActivityBuilders\DirectMessageActivityBuilder;
use App\Jobs\Federation\DeliverDirectMessageActivity;
use App\Jobs\PushNotificationPipeline\MentionPushNotifyPipeline;
use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\User;
use App\Util\ActivityPub\Helpers;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DirectMessageService
{
    public function canUseDirectMessages(User $user): bool
    {
        if ($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id)) {
            return false;
        }

        return true;
    }

    /**
     * New accounts have to wait before they can message anyone, unless the
     * admin turned that off.
     */
    public function canInitiateConversation(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ((bool) config_cache('instance.allow_new_account_dms')) {
            return true;
        }

        return ! $user->created_at->gt(now()->subHours(72));
    }

    /**
     * Whether $sender may message $recipient at all. Blocks in either
     * direction, domain blocks, and unreachable or disabled accounts all say
     * no. Privacy settings do not: those turn the conversation into a request.
     */
    public function canMessage(Profile $sender, Profile $recipient): bool
    {
        if ($sender->id === $recipient->id) {
            return false;
        }

        if ($recipient->status !== null || $sender->status !== null) {
            return false;
        }

        if ($this->isBlockedBy($recipient, $sender) || $this->isBlockedBy($sender, $recipient)) {
            return false;
        }

        if ($recipient->domain !== null) {
            if ($sender->domain !== null) {
                return false;
            }

            if (! $recipient->inbox_url && ! $recipient->sharedInbox) {
                return false;
            }

            return (bool) config('federation.activitypub.enabled');
        }

        return (bool) $recipient->user_id;
    }

    /**
     * True when $owner blocks $other, either the account or its whole domain.
     */
    public function isBlockedBy(Profile $owner, Profile $other): bool
    {
        if ($owner->domain !== null) {
            return false;
        }

        $blocks = UserFilterService::blocks($owner->id);

        if ($blocks && in_array($other->id, array_map('intval', $blocks), true)) {
            return true;
        }

        if ($other->domain && AccountService::blocksDomain($owner->id, $other->domain) === true) {
            return true;
        }

        return false;
    }

    /**
     * The state a recipient joins a conversation in. Someone who only takes
     * messages from people they follow gets a request instead.
     */
    public function initialState(Profile $recipient, Profile $sender): string
    {
        if ($recipient->domain !== null || $recipient->id === $sender->id) {
            return DmConversationParticipant::STATE_ACTIVE;
        }

        $acceptsEveryone = (bool) optional(optional($recipient->user)->settings)->public_dm;

        if ($acceptsEveryone && ! $recipient->is_private) {
            return DmConversationParticipant::STATE_ACTIVE;
        }

        return $recipient->follows($sender)
            ? DmConversationParticipant::STATE_ACTIVE
            : DmConversationParticipant::STATE_REQUEST;
    }

    public function findDm(int $a, int $b): ?DmConversation
    {
        return DmConversation::where('participants_hash', DmConversation::dmHash($a, $b))->first();
    }

    public function findOrCreateDm(Profile $sender, Profile $recipient): DmConversation
    {
        return $this->findOrCreateConversation($sender, collect([$recipient]));
    }

    /**
     * Find the conversation these people share, or start it.
     *
     * @param  Collection<int, Profile>  $others
     * @param  array{context_uri?: ?string, conversation_uri?: ?string, name?: ?string}  $attributes
     */
    public function findOrCreateConversation(Profile $creator, Collection $others, array $attributes = []): DmConversation
    {
        $profiles = collect([$creator])->concat($others)->unique('id')->values();

        if ($profiles->count() < 2) {
            throw new DirectMessageException('A conversation needs at least two participants.', 422);
        }

        if ($profiles->count() > 2) {
            if (! config('dm.groups.enabled')) {
                throw new DirectMessageException('Group conversations are not enabled.', 422);
            }

            if ($profiles->count() > (int) config('dm.groups.max_participants')) {
                throw new DirectMessageException('Too many participants.', 422);
            }
        }

        $hash = DmConversation::participantsHash($profiles->pluck('id')->all());

        $conversation = DmConversation::where('participants_hash', $hash)->first();

        if ($conversation) {
            return $conversation;
        }

        try {
            return DB::transaction(function () use ($creator, $profiles, $hash, $attributes) {
                $conversation = new DmConversation;
                $conversation->id = SnowflakeService::next();
                $conversation->type = $profiles->count() > 2 ? DmConversation::TYPE_GROUP : DmConversation::TYPE_DM;
                $conversation->participants_hash = $hash;
                $conversation->name = $attributes['name'] ?? null;
                $conversation->context_uri = ($attributes['context_uri'] ?? null)
                    ?: DmConversation::localContextUri($conversation->id);
                $conversation->conversation_uri = $attributes['conversation_uri'] ?? null;
                $conversation->created_by_profile_id = $creator->id;
                $conversation->save();

                $now = now();

                DmConversationParticipant::insert($profiles->map(fn (Profile $profile) => [
                    'conversation_id' => $conversation->id,
                    'profile_id' => $profile->id,
                    'state' => $this->initialState($profile, $creator),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());

                return $conversation;
            });
        } catch (QueryException $e) {
            // Lost a race to create the same conversation
            $conversation = DmConversation::where('participants_hash', $hash)->first();

            if ($conversation) {
                return $conversation;
            }

            throw $e;
        }
    }

    public function participant(DmConversation|int $conversation, int $profileId): ?DmConversationParticipant
    {
        $id = $conversation instanceof DmConversation ? $conversation->id : $conversation;

        return DmConversationParticipant::where('conversation_id', $id)
            ->where('profile_id', $profileId)
            ->first();
    }

    /**
     * The conversation, but only for someone who is in it and has not left.
     *
     * @return array{0: DmConversation, 1: DmConversationParticipant}|null
     */
    public function conversationFor(int|string $conversationId, int $profileId): ?array
    {
        $participant = DmConversationParticipant::where('conversation_id', $conversationId)
            ->where('profile_id', $profileId)
            ->where('state', '!=', DmConversationParticipant::STATE_LEFT)
            ->first();

        if (! $participant) {
            return null;
        }

        $conversation = DmConversation::find($conversationId);

        return $conversation ? [$conversation, $participant] : null;
    }

    /**
     * Profile ids of everyone in the conversation.
     *
     * @return array<int, int>
     */
    public function participantIds(DmConversation|int $conversation): array
    {
        $id = $conversation instanceof DmConversation ? $conversation->id : $conversation;

        return DmConversationParticipant::where('conversation_id', $id)
            ->orderBy('id')
            ->pluck('profile_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Remote servers are the source of truth for the thread identifiers, so
     * the newest inbound values win and are what our replies carry.
     */
    public function adoptContext(DmConversation $conversation, ?string $contextUri, ?string $conversationUri): void
    {
        $dirty = false;

        if ($contextUri && strlen($contextUri) <= 1024 && $conversation->context_uri !== $contextUri) {
            $conversation->context_uri = $contextUri;
            $dirty = true;
        }

        if ($conversationUri && strlen($conversationUri) <= 1024 && $conversation->conversation_uri !== $conversationUri) {
            $conversation->conversation_uri = $conversationUri;
            $dirty = true;
        }

        if ($dirty) {
            $conversation->save();
        }
    }

    /**
     * Send a message from a local profile.
     *
     * @param  array{body?: ?string, type?: ?string, media?: ?Collection, in_reply_to_id?: ?int, is_sensitive?: bool}  $data
     */
    public function sendMessage(DmConversation $conversation, Profile $sender, array $data): DmMessage
    {
        $participant = $this->participant($conversation, $sender->id);

        if (! $participant || $participant->hasLeft()) {
            throw new DirectMessageException('You are not part of this conversation.', 403);
        }

        $body = isset($data['body']) ? trim((string) $data['body']) : '';
        $media = $data['media'] ?? collect();

        if ($body === '' && $media->isEmpty()) {
            throw new DirectMessageException('A message needs text or media.', 422);
        }

        $others = Profile::whereIn('id', array_diff($this->participantIds($conversation), [$sender->id]))->get();

        if (! $conversation->isGroup()) {
            $recipient = $others->first();

            if (! $recipient || ! $this->canMessage($sender, $recipient)) {
                throw new DirectMessageException('You cannot message this account.', 403);
            }

            $this->enforceRequestLimit($conversation, $sender, $recipient);
        } elseif ($others->filter(fn (Profile $other) => $other->status === null)->isEmpty()) {
            throw new DirectMessageException('Nobody in this conversation can be reached.', 403);
        }

        if (! empty($data['in_reply_to_id'])) {
            $parentExists = DmMessage::where('conversation_id', $conversation->id)
                ->where('id', $data['in_reply_to_id'])
                ->exists();

            if (! $parentExists) {
                throw new DirectMessageException('Invalid in_reply_to_id.', 422);
            }
        }

        $message = $this->storeMessage($conversation, $sender, [
            'body' => $body === '' ? null : $body,
            'type' => $data['type'] ?? null,
            'media' => $media,
            'in_reply_to_id' => $data['in_reply_to_id'] ?? null,
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
        ]);

        $this->federateCreate($message, $conversation, $sender, $others);

        return $message;
    }

    /**
     * Until a request is accepted the sender only gets a few messages in.
     */
    protected function enforceRequestLimit(DmConversation $conversation, Profile $sender, Profile $recipient): void
    {
        if ($recipient->domain !== null) {
            return;
        }

        $state = $this->participant($conversation, $recipient->id);

        if (! $state || ! $state->isRequest()) {
            return;
        }

        $sent = DmMessage::where('conversation_id', $conversation->id)
            ->where('profile_id', $sender->id)
            ->count();

        if ($sent >= (int) config('dm.requests.sender_limit')) {
            throw new DirectMessageException('You can send more messages once your request is accepted.', 403);
        }
    }

    /**
     * Write a message and bring the conversation up to date. Shared by local
     * sends, inbound federation, story replies and the backfill.
     *
     * @param  array{
     *     body?: ?string,
     *     type?: ?string,
     *     media?: ?Collection,
     *     meta?: ?array,
     *     entities?: ?array,
     *     ap_object_uri?: ?string,
     *     in_reply_to_id?: ?int,
     *     status_id?: ?int,
     *     is_sensitive?: bool,
     *     notify?: bool,
     *     notification_action?: string,
     * }  $data
     */
    public function storeMessage(DmConversation $conversation, Profile $sender, array $data): DmMessage
    {
        $media = $data['media'] ?? collect();
        $body = $data['body'] ?? null;

        [$type, $meta] = $this->resolveType($data['type'] ?? null, $body, $media, $data['meta'] ?? null);

        $message = DB::transaction(function () use ($conversation, $sender, $data, $media, $body, $type, $meta) {
            $message = new DmMessage;
            $message->id = SnowflakeService::next();
            $message->conversation_id = $conversation->id;
            $message->profile_id = $sender->id;
            $message->type = $type;
            $message->body = $body;
            $message->meta = $meta;
            $message->entities = $data['entities'] ?? null;
            $message->is_sensitive = (bool) ($data['is_sensitive'] ?? false);
            $message->in_reply_to_id = $data['in_reply_to_id'] ?? null;
            $message->status_id = $data['status_id'] ?? null;
            $message->ap_object_uri = ($data['ap_object_uri'] ?? null) ?: DmMessage::localObjectUri($message->id);
            $message->save();

            $position = 0;
            foreach ($media as $item) {
                DB::table('dm_message_media')->insert([
                    'message_id' => $message->id,
                    'media_id' => $item->id,
                    'position' => $position++,
                ]);
            }

            $conversation->last_message_id = $message->id;
            $conversation->last_message_at = $message->created_at;
            $conversation->save();

            return $message;
        });

        $this->fanOut($conversation, $message, $sender, [
            'notify' => $data['notify'] ?? true,
            'action' => $data['notification_action'] ?? 'dm',
        ]);

        return $message;
    }

    /**
     * Update every participant's view of the conversation. A recipient who
     * blocks the sender is skipped without anyone being told: they get no
     * unread count, no notification, and the message is filtered out when
     * they read the conversation.
     *
     * @param  array{notify: bool, action: string}  $options
     */
    protected function fanOut(DmConversation $conversation, DmMessage $message, Profile $sender, array $options): void
    {
        $now = $message->created_at ?? now();

        $participants = DmConversationParticipant::where('conversation_id', $conversation->id)->get();
        $profiles = Profile::whereIn('id', $participants->pluck('profile_id'))->get()->keyBy('id');

        foreach ($participants as $participant) {
            $profile = $profiles->get($participant->profile_id);

            if (! $profile) {
                continue;
            }

            if ($profile->id === $sender->id) {
                $participant->last_read_message_id = $message->id;
                $participant->last_activity_at = $now;
                $participant->unread_count = 0;
                $participant->hidden_at = null;

                // Replying to a request accepts it
                if ($participant->isRequest()) {
                    $participant->state = DmConversationParticipant::STATE_ACTIVE;
                }

                $participant->save();

                continue;
            }

            if ($profile->domain !== null || $participant->hasLeft()) {
                continue;
            }

            if ($this->isBlockedBy($profile, $sender)) {
                continue;
            }

            $participant->unread_count = $participant->unread_count + 1;
            $participant->last_activity_at = $now;
            $participant->save();

            if (
                $options['notify'] &&
                $participant->isActive() &&
                ! $participant->muted_at &&
                ! $participant->hidden_at
            ) {
                $this->notify($profile, $sender, $message, $options['action']);
            }
        }
    }

    protected function notify(Profile $recipient, Profile $sender, DmMessage $message, string $action): void
    {
        NotificationService::createNotification(
            $recipient->id,
            $sender->id,
            $action,
            $message->id,
            DmMessage::class
        );

        if (! NotificationAppGatewayService::enabled()) {
            return;
        }

        if (! PushNotificationService::check('mention', $recipient->id)) {
            return;
        }

        $user = User::whereProfileId($recipient->id)->first();

        if ($user && $user->expo_token && $user->notify_enabled) {
            MentionPushNotifyPipeline::dispatch($user->expo_token, $sender->username)->onQueue('pushnotify');
        }
    }

    /**
     * Legacy clients pick a renderer from `type`, newer ones read `text` and
     * `media` and treat it as a hint.
     *
     * @return array{0: string, 1: ?array}
     */
    protected function resolveType(?string $requested, ?string $body, Collection $media, ?array $meta): array
    {
        if (in_array($requested, [DmMessage::TYPE_STORY_REACT, DmMessage::TYPE_STORY_COMMENT], true)) {
            return [$requested, $meta];
        }

        if ($media->isNotEmpty()) {
            $photos = $media->filter(fn ($m) => str_starts_with((string) $m->mime, 'image/'))->count();
            $videos = $media->filter(fn ($m) => str_starts_with((string) $m->mime, 'video/'))->count();

            $type = match (true) {
                $photos > 0 && $videos > 0 => DmMessage::TYPE_MEDIA,
                $videos > 1 => DmMessage::TYPE_VIDEOS,
                $videos === 1 => DmMessage::TYPE_VIDEO,
                $photos > 1 => DmMessage::TYPE_PHOTOS,
                default => DmMessage::TYPE_PHOTO,
            };

            return [$type, $meta];
        }

        if ($body && filter_var($body, FILTER_VALIDATE_URL) && Helpers::validateUrl($body)) {
            $host = parse_url($body, PHP_URL_HOST);

            return [DmMessage::TYPE_LINK, array_merge($meta ?? [], [
                'domain' => $host,
                'local' => $host === parse_url(config('app.url'), PHP_URL_HOST),
            ])];
        }

        if ($requested === DmMessage::TYPE_EMOJI) {
            return [DmMessage::TYPE_EMOJI, $meta];
        }

        return [DmMessage::TYPE_TEXT, $meta];
    }

    /**
     * Story reactions and replies show up in the conversation between the
     * viewer and the story author. Their federation is handled by the story
     * pipeline, which uses $statusId as the ActivityPub object.
     */
    public function storeStoryMessage(Profile $sender, Profile $storyAuthor, string $type, ?string $text, array $meta, ?int $statusId = null, ?string $objectUri = null): DmMessage
    {
        $conversation = $this->findOrCreateDm($sender, $storyAuthor);

        // A story reply is only possible between people who already follow
        // each other's stories, so it never sits in requests
        DmConversationParticipant::where('conversation_id', $conversation->id)
            ->where('state', DmConversationParticipant::STATE_REQUEST)
            ->update(['state' => DmConversationParticipant::STATE_ACTIVE]);

        return $this->storeMessage($conversation, $sender, [
            'type' => $type,
            'body' => $text,
            'meta' => $meta,
            'status_id' => $statusId,
            'ap_object_uri' => $objectUri,
            'notify' => $storyAuthor->domain === null,
            'notification_action' => $type,
        ]);
    }

    /**
     * Uploaded media the sender may attach: their own, not on a post, not
     * already in another message.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Media>
     */
    public function attachableMedia(User $user, array $ids): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (empty($ids)) {
            return collect();
        }

        if (count($ids) > (int) config('dm.max_media')) {
            throw new DirectMessageException('Too many attachments.', 422);
        }

        $media = Media::whereIn('id', $ids)
            ->where('user_id', $user->id)
            ->where('profile_id', $user->profile_id)
            ->whereNull('status_id')
            ->notInDirectMessage()
            ->get()
            ->keyBy('id');

        if ($media->count() !== count($ids)) {
            throw new DirectMessageException('Invalid media_ids.', 422);
        }

        return collect($ids)->map(fn ($id) => $media->get($id))->values();
    }

    public static function isMessageMedia(int|string $mediaId): bool
    {
        return DB::table('dm_message_media')->where('media_id', $mediaId)->exists();
    }

    /**
     * Remove a message for everyone. Local authors also tell the other
     * servers in the conversation.
     */
    public function deleteMessage(DmMessage $message, bool $federate = true): void
    {
        $conversation = DmConversation::find($message->conversation_id);
        $sender = Profile::find($message->profile_id);

        // Story reactions and replies are federated by the story pipeline
        $isStory = in_array($message->type, [DmMessage::TYPE_STORY_REACT, DmMessage::TYPE_STORY_COMMENT], true);

        if ($federate && ! $isStory && $conversation && $sender && $sender->domain === null) {
            $this->federateDelete($message, $conversation, $sender);
        }

        $mediaIds = DB::table('dm_message_media')->where('message_id', $message->id)->pluck('media_id');

        if ($mediaIds->isNotEmpty()) {
            DB::table('dm_message_media')->where('message_id', $message->id)->delete();

            // Backfilled legacy media still belongs to its direct status and
            // goes away with it, everything else is ours to remove
            Media::whereIn('id', $mediaIds)->whereNull('status_id')->get()
                ->each(fn (Media $media) => MediaStorageService::delete($media, true));
        }

        Notification::where('item_type', DmMessage::class)
            ->where('item_id', $message->id)
            ->get()
            ->each(function (Notification $notification) {
                NotificationService::del($notification->profile_id, $notification->id);
                $notification->forceDelete();
            });

        $message->delete();

        if ($conversation) {
            $this->refreshAfterDelete($conversation, $message);
        }
    }

    protected function refreshAfterDelete(DmConversation $conversation, DmMessage $deleted): void
    {
        if ((int) $conversation->last_message_id === (int) $deleted->id) {
            $latest = DmMessage::where('conversation_id', $conversation->id)->orderByDesc('id')->first();

            $conversation->last_message_id = $latest?->id;
            $conversation->last_message_at = $latest?->created_at;
            $conversation->save();
        }

        DmConversationParticipant::where('conversation_id', $conversation->id)
            ->where('profile_id', '!=', $deleted->profile_id)
            ->where('unread_count', '>', 0)
            ->where(function ($query) use ($deleted) {
                $query->whereNull('last_read_message_id')
                    ->orWhere('last_read_message_id', '<', $deleted->id);
            })
            ->decrement('unread_count');
    }

    /**
     * Story reactions and legacy rows hang off a direct status. When that
     * status is deleted the message goes with it.
     */
    public function deleteByStatusId(int|string $statusId): void
    {
        DmMessage::where('status_id', $statusId)->get()
            ->each(fn (DmMessage $message) => $this->deleteMessage($message, false));
    }

    /**
     * Account deletion: drop what the profile wrote and take it out of its
     * conversations. Conversations nobody else is left in are removed.
     */
    public function purgeProfile(int $profileId): void
    {
        DmMessage::where('profile_id', $profileId)
            ->chunkById(200, function ($messages) {
                foreach ($messages as $message) {
                    $this->deleteMessage($message, false);
                }
            });

        $conversationIds = DmConversationParticipant::where('profile_id', $profileId)->pluck('conversation_id');

        foreach ($conversationIds->chunk(200) as $chunk) {
            $direct = DmConversation::whereIn('id', $chunk)
                ->where('type', DmConversation::TYPE_DM)
                ->pluck('id');

            if ($direct->isNotEmpty()) {
                DmMessage::whereIn('conversation_id', $direct)
                    ->chunkById(200, function ($messages) {
                        foreach ($messages as $message) {
                            $this->deleteMessage($message, false);
                        }
                    });

                DmConversationParticipant::whereIn('conversation_id', $direct)->delete();
                DmConversation::whereIn('id', $direct)->delete();
            }
        }

        DmConversationParticipant::where('profile_id', $profileId)
            ->update(['state' => DmConversationParticipant::STATE_LEFT, 'unread_count' => 0]);
    }

    public function markRead(DmConversationParticipant $participant, ?int $upToMessageId = null): void
    {
        $query = DmMessage::where('conversation_id', $participant->conversation_id);

        $latestId = $upToMessageId
            ? (clone $query)->where('id', '<=', $upToMessageId)->max('id')
            : (clone $query)->max('id');

        if (! $latestId) {
            return;
        }

        if ($participant->last_read_message_id && $participant->last_read_message_id >= $latestId) {
            return;
        }

        $participant->last_read_message_id = $latestId;
        $participant->unread_count = (clone $query)
            ->where('id', '>', $latestId)
            ->where('profile_id', '!=', $participant->profile_id)
            ->count();
        $participant->save();
    }

    public function accept(DmConversationParticipant $participant): void
    {
        if ($participant->isRequest()) {
            $participant->state = DmConversationParticipant::STATE_ACTIVE;
            $participant->save();
        }
    }

    public function setMuted(DmConversationParticipant $participant, bool $muted): void
    {
        $participant->muted_at = $muted ? ($participant->muted_at ?? now()) : null;
        $participant->save();
    }

    public function setHidden(DmConversationParticipant $participant, bool $hidden): void
    {
        $participant->hidden_at = $hidden ? ($participant->hidden_at ?? now()) : null;
        $participant->save();
    }

    /**
     * Leaving only changes what this person sees. The participant set is the
     * conversation's identity on the network, so it never shrinks.
     */
    public function leave(DmConversation $conversation, DmConversationParticipant $participant): void
    {
        if (! $conversation->isGroup()) {
            throw new DirectMessageException('Only group conversations can be left.', 422);
        }

        $participant->state = DmConversationParticipant::STATE_LEFT;
        $participant->unread_count = 0;
        $participant->save();
    }

    /**
     * @param  Collection<int, Profile>  $others
     */
    protected function federateCreate(DmMessage $message, DmConversation $conversation, Profile $sender, Collection $others): void
    {
        $inboxes = $this->remoteInboxes($others);

        if (empty($inboxes) || ! config('federation.activitypub.enabled')) {
            return;
        }

        $activity = app(DirectMessageActivityBuilder::class)->buildCreate($message, $conversation, $sender, $others);

        foreach ($inboxes as $inbox) {
            DeliverDirectMessageActivity::dispatch($sender->id, $inbox, $activity)->onQueue('high');
        }
    }

    protected function federateDelete(DmMessage $message, DmConversation $conversation, Profile $sender): void
    {
        if (! config('federation.activitypub.enabled')) {
            return;
        }

        $others = Profile::whereIn('id', array_diff($this->participantIds($conversation), [$sender->id]))->get();
        $inboxes = $this->remoteInboxes($others);

        if (empty($inboxes)) {
            return;
        }

        $activity = app(DirectMessageActivityBuilder::class)->buildDelete($message, $sender, $others);

        foreach ($inboxes as $inbox) {
            DeliverDirectMessageActivity::dispatch($sender->id, $inbox, $activity)->onQueue('high');
        }
    }

    /**
     * One delivery per server: people behind the same shared inbox get a
     * single copy.
     *
     * @param  Collection<int, Profile>  $profiles
     * @return array<int, string>
     */
    public function remoteInboxes(Collection $profiles): array
    {
        return $profiles
            ->filter(fn (Profile $profile) => $profile->domain !== null && $profile->status === null)
            ->map(fn (Profile $profile) => $profile->sharedInbox ?: $profile->inbox_url)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
