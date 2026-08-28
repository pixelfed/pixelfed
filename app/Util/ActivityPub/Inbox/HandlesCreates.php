<?php

namespace App\Util\ActivityPub\Inbox;

use App\DirectMessage;
use App\Jobs\PushNotificationPipeline\MentionPushNotifyPipeline;
use App\Media;
use App\Models\Conversation;
use App\Models\PollVote;
use App\Notification;
use App\Profile;
use App\Services\FollowerService;
use App\Services\NotificationAppGatewayService;
use App\Services\NotificationService;
use App\Services\PollService;
use App\Services\PushNotificationService;
use App\Services\SanitizeService;
use App\Status;
use App\User;
use App\UserFilter;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait HandlesCreates
{
    public function handleCreateActivity(): void
    {
        $activity = $this->payload['object'];

        if ($this->isContentFilteredBySpam($activity)) {
            return;
        }

        $actor = $this->validateAndFetchActor($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (! isset($activity['to'])) {
            return;
        }

        $to = $this->normalizeRecipients($activity['to'] ?? []);
        $cc = $this->normalizeRecipients($activity['cc'] ?? []);

        if ($activity['type'] == 'Question') {
            return;
        }

        if ($this->isDirectMessage($to, $cc)) {
            $this->handleDirectMessage();

            return;
        }

        if ($activity['type'] == 'Note' && ! empty($activity['inReplyTo'])) {
            $this->handleNoteReply();
        } elseif ($activity['type'] == 'Note' && ! empty($activity['attachment'])) {
            if (! $this->verifyNoteAttachment()) {
                return;
            }
            $this->handleNoteCreate();
        }
    }

    public function handleNoteReply(): void
    {
        $activity = $this->payload['object'];
        $actor = $this->validateAndFetchActor($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        $url = $activity['url'] ?? $activity['id'];
        Helpers::statusFirstOrFetch($url, true);
    }

    public function handlePollCreate(): void
    {
        $activity = $this->payload['object'];
        $actor = $this->validateAndFetchActor($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }
        $url = $activity['url'] ?? $activity['id'];
        Helpers::statusFirstOrFetch($url);
    }

    public function handleNoteCreate(): void
    {
        $activity = $this->payload['object'];
        $actor = $this->validateAndFetchActor($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (
            isset($activity['inReplyTo']) &&
            isset($activity['name']) &&
            ! isset($activity['content']) &&
            ! isset($activity['attachment']) &&
            Helpers::validateLocalUrl($activity['inReplyTo'])
        ) {
            $this->handlePollVote();

            return;
        }

        if ($actor->followers_count == 0) {
            if (config('federation.activitypub.ingest.store_notes_without_followers')) {
                // allowed — continue
            } elseif (FollowerService::followerCount($actor->id, true) == 0) {
                return;
            }
        }

        $hasUrl = isset($activity['url']);
        $url = $activity['url'] ?? $activity['id'];

        if ($hasUrl) {
            if (Status::whereUri($url)->exists()) {
                return;
            }
        } else {
            if (Status::whereObjectUrl($url)->exists()) {
                return;
            }
        }

        Helpers::storeStatus($url, $actor, $activity);
    }

    public function handlePollVote(): void
    {
        $activity = $this->payload['object'];
        $actor = $this->validateAndFetchActor($this->payload['actor']);

        if (! $actor) {
            return;
        }

        $status = Helpers::statusFetch($activity['inReplyTo']);

        if (! $status) {
            return;
        }

        $poll = $status->poll;

        if (! $poll) {
            return;
        }

        if (now()->gt($poll->expires_at)) {
            return;
        }

        $choices = $poll->poll_options;
        $choice = array_search($activity['name'], $choices);

        if ($choice === false) {
            return;
        }

        if (PollVote::whereStatusId($status->id)->whereProfileId($actor->id)->exists()) {
            return;
        }

        $vote = new PollVote;
        $vote->status_id = $status->id;
        $vote->profile_id = $actor->id;
        $vote->poll_id = $poll->id;
        $vote->choice = $choice;
        $vote->uri = $activity['id'] ?? null;
        $vote->save();

        $tallies = $poll->cached_tallies;
        $tallies[$choice] = $tallies[$choice] + 1;
        $poll->cached_tallies = $tallies;
        $poll->votes_count = array_sum($tallies);
        $poll->save();

        PollService::del($status->id);
    }

    public function handleDirectMessage(): void
    {
        $activity = $this->payload['object'];
        $to = $this->normalizeRecipients($activity['to'] ?? []);

        $actor = $this->validateAndFetchActor($this->payload['actor']);
        $profile = Profile::whereNull('domain')
            ->whereUsername(Arr::last(explode('/', $to[0])))
            ->firstOrFail();

        if (! $actor || in_array($actor->id, $profile->blockedIds()->toArray())) {
            return;
        }

        if ($this->isDomainBlocked($profile->id, $actor->domain)) {
            return;
        }

        $msgText = $this->sanitizeDirectMessageContent($activity['content'], $profile->username);
        $hidden = $this->determineDirectMessageVisibility($profile, $actor);

        $status = new Status;
        $status->profile_id = $actor->id;
        $status->caption = $msgText;
        $status->visibility = 'direct';
        $status->scope = 'direct';
        $status->url = $activity['id'];
        $status->uri = $activity['id'];
        $status->object_url = $activity['id'];
        $status->in_reply_to_profile_id = $profile->id;
        $status->save();

        $dm = new DirectMessage;
        $dm->to_id = $profile->id;
        $dm->from_id = $actor->id;
        $dm->status_id = $status->id;
        $dm->is_hidden = $hidden;
        $dm->type = 'text';
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $profile->id,
                'from_id' => $actor->id,
            ],
            [
                'type' => 'text',
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => $hidden,
            ]
        );

        $this->processDirectMessageAttachments($activity, $status, $dm);
        $this->processDirectMessageLink($msgText, $dm);
        $this->notifyDirectMessageRecipient($profile, $actor, $dm, $hidden);
    }

    /**
     * Check if content matches autospam live filters.
     */
    protected function isContentFilteredBySpam(array $activity): bool
    {
        if (! config('autospam.live_filters.enabled')) {
            return false;
        }

        $filters = config('autospam.live_filters.filters');
        if (empty($filters) || ! isset($activity['content']) || empty($activity['content']) || strlen($filters) <= 3) {
            return false;
        }

        $filters = array_map('trim', explode(',', $filters));
        $content = strtolower($activity['content']);

        foreach ($filters as $filter) {
            $filter = trim(strtolower($filter));
            if (! $filter || ! strlen($filter)) {
                continue;
            }
            if (str_contains($content, $filter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize recipients to always be an array (JSON-LD allows single strings).
     */
    protected function normalizeRecipients(mixed $recipients): array
    {
        if (is_string($recipients)) {
            return [$recipients];
        }

        return is_array($recipients) ? $recipients : [];
    }

    /**
     * Determine if the activity is a direct message (single local recipient, no cc).
     */
    protected function isDirectMessage(array $to, array $cc): bool
    {
        return is_array($to) &&
            is_array($cc) &&
            count($to) == 1 &&
            count($cc) == 0 &&
            parse_url($to[0], PHP_URL_HOST) == config('pixelfed.domain.app');
    }

    /**
     * Sanitize DM content and strip leading @mention of the recipient.
     */
    protected function sanitizeDirectMessageContent(string $content, string $username): string
    {
        $msg = app(SanitizeService::class)->html($content);
        $msgText = strip_tags($msg);

        if (Str::startsWith($msgText, '@'.$username)) {
            $len = strlen('@'.$username);
            $msgText = substr($msgText, $len + 1);
        }

        return $msgText;
    }

    /**
     * Determine if a DM should be hidden based on recipient privacy settings.
     */
    protected function determineDirectMessageVisibility(Profile $profile, Profile $actor): bool
    {
        if ($profile->user->settings->public_dm == false || $profile->is_private) {
            return $profile->follows($actor) != true;
        }

        return false;
    }

    /**
     * Process attachments on a direct message.
     */
    protected function processDirectMessageAttachments(array $activity, Status $status, DirectMessage $dm): void
    {
        if (! count($activity['attachment'] ?? [])) {
            return;
        }

        $photos = 0;
        $videos = 0;
        $allowed = explode(',', config_cache('pixelfed.media_types'));
        $attachments = array_slice($activity['attachment'], 0, config_cache('pixelfed.max_album_length'));

        foreach ($attachments as $a) {
            $type = $a['mediaType'];
            $url = $a['url'];

            if (! in_array($type, $allowed) || ! Helpers::validateUrl($url)) {
                continue;
            }

            $media = new Media;
            $media->remote_media = true;
            $media->status_id = $status->id;
            $media->profile_id = $status->profile_id;
            $media->user_id = null;
            $media->media_path = $url;
            $media->remote_url = $url;
            $media->mime = $type;
            $media->save();

            if (explode('/', $type)[0] == 'image') {
                $photos++;
            }
            if (explode('/', $type)[0] == 'video') {
                $videos++;
            }
        }

        if ($photos && $videos == 0) {
            $dm->type = $photos == 1 ? 'photo' : 'photos';
            $dm->save();
        }
        if ($videos && $photos == 0) {
            $dm->type = $videos == 1 ? 'video' : 'videos';
            $dm->save();
        }
    }

    /**
     * If the DM text is a valid URL, mark the DM type as 'link'.
     */
    protected function processDirectMessageLink(string $msgText, DirectMessage $dm): void
    {
        if (! filter_var($msgText, FILTER_VALIDATE_URL)) {
            return;
        }

        if (! Helpers::validateUrl($msgText)) {
            return;
        }

        $dm->type = 'link';
        $dm->meta = [
            'domain' => parse_url($msgText, PHP_URL_HOST),
            'local' => parse_url($msgText, PHP_URL_HOST) == parse_url(config('app.url'), PHP_URL_HOST),
        ];
        $dm->save();
    }

    /**
     * Send notification to DM recipient if applicable.
     */
    protected function notifyDirectMessageRecipient(Profile $profile, Profile $actor, DirectMessage $dm, bool $hidden): void
    {
        $isMuted = UserFilter::whereUserId($profile->id)
            ->whereFilterableId($actor->id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('dm.mute')
            ->exists();

        if ($profile->domain != null || $hidden || $isMuted) {
            return;
        }

        NotificationService::createNotification($profile->id, $actor->id, 'dm', $dm->id, DirectMessage::class);

        if (NotificationAppGatewayService::enabled()) {
            if (PushNotificationService::check('mention', $profile->id)) {
                $user = User::whereProfileId($profile->id)->first();
                if ($user && $user->expo_token && $user->notify_enabled) {
                    MentionPushNotifyPipeline::dispatch($user->expo_token, $actor->username)->onQueue('pushnotify');
                }
            }
        }
    }
}
