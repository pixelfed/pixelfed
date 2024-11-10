<?php

namespace App\Util\ActivityPub;

use App\DirectMessage;
use App\Follower;
use App\FollowRequest;
use App\Instance;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Jobs\HomeFeedPipeline\FeedRemoveRemotePipeline;
use App\Jobs\LikePipeline\LikePipeline;
use App\Jobs\MovePipeline\CleanupLegacyAccountMovePipeline;
use App\Jobs\MovePipeline\MoveMigrateFollowersPipeline;
use App\Jobs\MovePipeline\ProcessMovePipeline;
use App\Jobs\MovePipeline\UnfollowLegacyAccountMovePipeline;
use App\Jobs\ProfilePipeline\HandleUpdateActivity;
use App\Jobs\PushNotificationPipeline\MentionPushNotifyPipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusRemoteUpdatePipeline;
use App\Jobs\StoryPipeline\StoryExpire;
use App\Jobs\StoryPipeline\StoryFetch;
use App\Like;
use App\Media;
use App\Models\Conversation;
use App\Models\RemoteReport;
use App\Notification;
use App\Profile;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\NotificationAppGatewayService;
use App\Services\PollService;
use App\Services\PushNotificationService;
use App\Services\ReblogService;
use App\Services\UserFilterService;
use App\Status;
use App\Story;
use App\StoryView;
use App\UserFilter;
use App\Util\ActivityPub\Validator\Accept as AcceptValidator;
use App\Util\ActivityPub\Validator\Announce as AnnounceValidator;
use App\Util\ActivityPub\Validator\Follow as FollowValidator;
use App\Util\ActivityPub\Validator\Like as LikeValidator;
use App\Util\ActivityPub\Validator\MoveValidator;
use App\Util\ActivityPub\Validator\UpdatePersonValidator;
use Cache;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Purify;
use Storage;
use Throwable;

class Inbox
{
    protected $headers;

    protected $profile;

    protected $payload;

    protected $logger;

    public function __construct($headers, $profile, $payload)
    {
        $this->headers = $headers;
        $this->profile = $profile;
        $this->payload = $payload;
    }

    public function handle()
    {
        $this->handleVerb();

    }

    public function handleVerb()
    {
        $verb = (string) $this->payload['type'];
        switch ($verb) {

            case 'Add':
                $this->handleAddActivity();
                break;

            case 'Create':
                $this->handleCreateActivity();
                break;

            case 'Follow':
                if (FollowValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleFollowActivity();
                break;

            case 'Announce':
                if (AnnounceValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleAnnounceActivity();
                break;

            case 'Accept':
                if (AcceptValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleAcceptActivity();
                break;

            case 'Delete':
                $this->handleDeleteActivity();
                break;

            case 'Like':
                if (LikeValidator::validate($this->payload) == false) {
                    return;
                }
                $this->handleLikeActivity();
                break;

            case 'Reject':
                $this->handleRejectActivity();
                break;

            case 'Undo':
                $this->handleUndoActivity();
                break;

            case 'View':
                $this->handleViewActivity();
                break;

            case 'Story:Reaction':
                $this->handleStoryReactionActivity();
                break;

            case 'Story:Reply':
                $this->handleStoryReplyActivity();
                break;

            case 'Flag':
                $this->handleFlagActivity();
                break;

            case 'Update':
                $this->handleUpdateActivity();
                break;

            case 'Move':
                if (MoveValidator::validate($this->payload) == false) {
                    Log::info('[AP][INBOX][MOVE] VALIDATE_FAILURE '.json_encode($this->payload));

                    return;
                }
                $this->handleMoveActivity();
                break;

            default:
                // TODO: decide how to handle invalid verbs.
                break;
        }
    }

    public function verifyNoteAttachment()
    {
        $activity = $this->payload['object'];

        if (isset($activity['inReplyTo']) &&
            ! empty($activity['inReplyTo']) &&
            Helpers::validateUrl($activity['inReplyTo'])
        ) {
            // reply detected, skip attachment check
            return true;
        }

        $valid = Helpers::verifyAttachments($activity);

        return $valid;
    }

    public function actorFirstOrCreate($actorUrl)
    {
        return Helpers::profileFetch($actorUrl);
    }

    public function handleAddActivity()
    {
        // stories ;)

        if (! isset(
            $this->payload['actor'],
            $this->payload['object']
        )) {
            return;
        }

        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];

        if (! Helpers::validateUrl($actor)) {
            return;
        }

        if (! isset($obj['type'])) {
            return;
        }

        switch ($obj['type']) {
            case 'Story':
                StoryFetch::dispatch($this->payload);
                break;
        }

    }

    public function handleCreateActivity()
    {
        $activity = $this->payload['object'];
        if (config('autospam.live_filters.enabled')) {
            $filters = config('autospam.live_filters.filters');
            if (! empty($filters) && isset($activity['content']) && ! empty($activity['content']) && strlen($filters) > 3) {
                $filters = array_map('trim', explode(',', $filters));
                $content = $activity['content'];
                foreach ($filters as $filter) {
                    $filter = trim(strtolower($filter));
                    if (! $filter || ! strlen($filter)) {
                        continue;
                    }
                    if (str_contains(strtolower($content), $filter)) {
                        return;
                    }
                }
            }
        }
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (! isset($activity['to'])) {
            return;
        }
        $to = isset($activity['to']) ? $activity['to'] : [];
        $cc = isset($activity['cc']) ? $activity['cc'] : [];

        if ($activity['type'] == 'Question') {
            //$this->handlePollCreate();

            return;
        }

        if (is_array($to) &&
            is_array($cc) &&
            count($to) == 1 &&
            count($cc) == 0 &&
            parse_url($to[0], PHP_URL_HOST) == config('pixelfed.domain.app')
        ) {
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

    public function handleNoteReply()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        $inReplyTo = $activity['inReplyTo'];
        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];

        Helpers::statusFirstOrFetch($url, true);

    }

    public function handlePollCreate()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }
        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];
        Helpers::statusFirstOrFetch($url);

    }

    public function handleNoteCreate()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (isset($activity['inReplyTo']) &&
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
            } elseif (FollowerService::followerCount($actor->id, true) == 0) {
                return;
            }
        }

        $hasUrl = isset($activity['url']);
        $url = isset($activity['url']) ? $activity['url'] : $activity['id'];

        if ($hasUrl) {
            if (Status::whereUri($url)->exists()) {
                return;
            }
        } else {
            if (Status::whereObjectUrl($url)->exists()) {
                return;
            }
        }

        Helpers::storeStatus(
            $url,
            $actor,
            $activity
        );

    }

    public function handlePollVote()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);

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
        $vote->uri = isset($activity['id']) ? $activity['id'] : null;
        $vote->save();

        $tallies = $poll->cached_tallies;
        $tallies[$choice] = $tallies[$choice] + 1;
        $poll->cached_tallies = $tallies;
        $poll->votes_count = array_sum($tallies);
        $poll->save();

        PollService::del($status->id);

    }

    public function handleDirectMessage()
    {
        $activity = $this->payload['object'];
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        $profile = Profile::whereNull('domain')
            ->whereUsername(array_last(explode('/', $activity['to'][0])))
            ->firstOrFail();

        if (! $actor || in_array($actor->id, $profile->blockedIds()->toArray())) {
            return;
        }

        if (AccountService::blocksDomain($profile->id, $actor->domain) == true) {
            return;
        }

        $msg = $activity['content'];
        $msgText = strip_tags($activity['content']);

        if (Str::startsWith($msgText, '@'.$profile->username)) {
            $len = strlen('@'.$profile->username);
            $msgText = substr($msgText, $len + 1);
        }

        if ($profile->user->settings->public_dm == false || $profile->is_private) {
            if ($profile->follows($actor) == true) {
                $hidden = false;
            } else {
                $hidden = true;
            }
        } else {
            $hidden = false;
        }

        $status = new Status;
        $status->profile_id = $actor->id;
        $status->caption = $msgText;
        $status->rendered = $msg;
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

        if (count($activity['attachment'])) {
            $photos = 0;
            $videos = 0;
            $allowed = explode(',', config_cache('pixelfed.media_types'));
            $activity['attachment'] = array_slice($activity['attachment'], 0, config_cache('pixelfed.max_album_length'));
            foreach ($activity['attachment'] as $a) {
                $type = $a['mediaType'];
                $url = $a['url'];
                $valid = Helpers::validateUrl($url);
                if (in_array($type, $allowed) == false || $valid == false) {
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
                    $photos = $photos + 1;
                }
                if (explode('/', $type)[0] == 'video') {
                    $videos = $videos + 1;
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

        if (filter_var($msgText, FILTER_VALIDATE_URL)) {
            if (Helpers::validateUrl($msgText)) {
                $dm->type = 'link';
                $dm->meta = [
                    'domain' => parse_url($msgText, PHP_URL_HOST),
                    'local' => parse_url($msgText, PHP_URL_HOST) ==
                        parse_url(config('app.url'), PHP_URL_HOST),
                ];
                $dm->save();
            }
        }

        $nf = UserFilter::whereUserId($profile->id)
            ->whereFilterableId($actor->id)
            ->whereFilterableType('App\Profile')
            ->whereFilterType('dm.mute')
            ->exists();

        if ($profile->domain == null && $hidden == false && ! $nf) {
            $notification = new Notification;
            $notification->profile_id = $profile->id;
            $notification->actor_id = $actor->id;
            $notification->action = 'dm';
            $notification->item_id = $dm->id;
            $notification->item_type = "App\DirectMessage";
            $notification->save();

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

    public function handleFollowActivity()
    {
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        $target = $this->actorFirstOrCreate($this->payload['object']);
        if (! $actor || ! $target) {
            return;
        }

        if ($actor->domain == null || $target->domain !== null) {
            return;
        }

        if (AccountService::blocksDomain($target->id, $actor->domain) == true) {
            return;
        }

        if (
            Follower::whereProfileId($actor->id)
                ->whereFollowingId($target->id)
                ->exists() ||
            FollowRequest::whereFollowerId($actor->id)
                ->whereFollowingId($target->id)
                ->exists()
        ) {
            return;
        }

        $blocks = UserFilterService::blocks($target->id);
        if ($blocks && in_array($actor->id, $blocks)) {
            return;
        }

        if ($target->is_private == true) {
            FollowRequest::updateOrCreate([
                'follower_id' => $actor->id,
                'following_id' => $target->id,
            ], [
                'activity' => collect($this->payload)->only(['id', 'actor', 'object', 'type'])->toArray(),
            ]);
        } else {
            $follower = new Follower;
            $follower->profile_id = $actor->id;
            $follower->following_id = $target->id;
            $follower->local_profile = empty($actor->domain);
            $follower->save();

            FollowPipeline::dispatch($follower);
            FollowerService::add($actor->id, $target->id);

            // send Accept to remote profile
            $accept = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $target->permalink().'#accepts/follows/'.$follower->id,
                'type' => 'Accept',
                'actor' => $target->permalink(),
                'object' => [
                    'id' => $this->payload['id'],
                    'actor' => $actor->permalink(),
                    'type' => 'Follow',
                    'object' => $target->permalink(),
                ],
            ];
            Helpers::sendSignedObject($target, $actor->inbox_url, $accept);
            Cache::forget('profile:follower_count:'.$target->id);
            Cache::forget('profile:follower_count:'.$actor->id);
            Cache::forget('profile:following_count:'.$target->id);
            Cache::forget('profile:following_count:'.$actor->id);
        }

    }

    public function handleAnnounceActivity()
    {
        $actor = $this->actorFirstOrCreate($this->payload['actor']);
        $activity = $this->payload['object'];

        if (! $actor || $actor->domain == null) {
            return;
        }

        $parent = Helpers::statusFetch($activity);

        if (! $parent || empty($parent)) {
            return;
        }

        if (AccountService::blocksDomain($parent->profile_id, $actor->domain) == true) {
            return;
        }

        $blocks = UserFilterService::blocks($parent->profile_id);
        if ($blocks && in_array($actor->id, $blocks)) {
            return;
        }

        $status = Status::firstOrCreate([
            'profile_id' => $actor->id,
            'reblog_of_id' => $parent->id,
            'type' => 'share',
        ]);

        Notification::firstOrCreate(
            [
                'profile_id' => $parent->profile_id,
                'actor_id' => $actor->id,
                'action' => 'share',
                'item_id' => $parent->id,
                'item_type' => 'App\Status',
            ]
        );

        $parent->reblogs_count = $parent->reblogs_count + 1;
        $parent->save();

        ReblogService::addPostReblog($parent->profile_id, $status->id);

    }

    public function handleAcceptActivity()
    {
        $actor = $this->payload['object']['actor'];
        $obj = $this->payload['object']['object'];
        $type = $this->payload['object']['type'];

        if ($type !== 'Follow') {
            return;
        }

        $actor = Helpers::validateLocalUrl($actor);
        $target = Helpers::validateUrl($obj);

        if (! $actor || ! $target) {
            return;
        }

        $actor = Helpers::profileFetch($actor);
        $target = Helpers::profileFetch($target);

        if (! $actor || ! $target) {
            return;
        }

        if (AccountService::blocksDomain($target->id, $actor->domain) == true) {
            return;
        }

        $request = FollowRequest::whereFollowerId($actor->id)
            ->whereFollowingId($target->id)
            ->whereIsRejected(false)
            ->first();

        if (! $request) {
            return;
        }

        $follower = Follower::firstOrCreate([
            'profile_id' => $actor->id,
            'following_id' => $target->id,
        ]);
        FollowPipeline::dispatch($follower);

        $request->delete();

    }

    public function handleDeleteActivity()
    {
        if (! isset(
            $this->payload['actor'],
            $this->payload['object']
        )) {
            return;
        }
        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];
        if (is_string($obj) == true && $actor == $obj && Helpers::validateUrl($obj)) {
            $profile = Profile::whereRemoteUrl($obj)->first();
            if (! $profile || $profile->private_key != null) {
                return;
            }
            DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');

            return;
        } else {
            if (! isset(
                $obj['id'],
                $this->payload['object'],
                $this->payload['object']['id'],
                $this->payload['object']['type']
            )) {
                return;
            }
            $type = $this->payload['object']['type'];
            $typeCheck = in_array($type, ['Person', 'Tombstone', 'Story']);
            if (! Helpers::validateUrl($actor) || ! Helpers::validateUrl($obj['id']) || ! $typeCheck) {
                return;
            }
            if (parse_url($obj['id'], PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
                return;
            }
            $id = $this->payload['object']['id'];
            switch ($type) {
                case 'Person':
                    $profile = Profile::whereRemoteUrl($actor)->first();
                    if (! $profile || $profile->private_key != null) {
                        return;
                    }
                    DeleteRemoteProfilePipeline::dispatch($profile)->onQueue('inbox');

                    return;
                    break;

                case 'Tombstone':
                    $profile = Profile::whereRemoteUrl($actor)->first();
                    if (! $profile || $profile->private_key != null) {
                        return;
                    }

                    $status = Status::where('object_url', $id)->first();
                    if (! $status) {
                        $status = Status::where('url', $id)->first();
                        if (! $status) {
                            return;
                        }
                    }
                    if ($status->profile_id != $profile->id) {
                        return;
                    }
                    if ($status->scope && in_array($status->scope, ['public', 'unlisted', 'private'])) {
                        if ($status->type && ! in_array($status->type, ['story:reaction', 'story:reply', 'reply'])) {
                            FeedRemoveRemotePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');
                        }
                    }
                    RemoteStatusDelete::dispatch($status)->onQueue('high');

                    return;
                    break;

                case 'Story':
                    $story = Story::whereObjectId($id)
                        ->first();
                    if ($story) {
                        StoryExpire::dispatch($story)->onQueue('story');
                    }

                    return;
                    break;

                default:
                    return;
                    break;
            }
        }

    }

    public function handleLikeActivity()
    {
        $actor = $this->payload['actor'];

        if (! Helpers::validateUrl($actor)) {
            return;
        }

        $profile = self::actorFirstOrCreate($actor);
        $obj = $this->payload['object'];
        if (! Helpers::validateUrl($obj)) {
            return;
        }
        $status = Helpers::statusFirstOrFetch($obj);
        if (! $status || ! $profile) {
            return;
        }

        if (AccountService::blocksDomain($status->profile_id, $profile->domain) == true) {
            return;
        }

        $blocks = UserFilterService::blocks($status->profile_id);
        if ($blocks && in_array($profile->id, $blocks)) {
            return;
        }

        $like = Like::firstOrCreate([
            'profile_id' => $profile->id,
            'status_id' => $status->id,
        ]);

        if ($like->wasRecentlyCreated == true) {
            $status->likes_count = $status->likes_count + 1;
            $status->save();
            LikePipeline::dispatch($like);
        }

    }

    public function handleRejectActivity() {}

    public function handleUndoActivity()
    {
        $actor = $this->payload['actor'];
        $profile = self::actorFirstOrCreate($actor);
        $obj = $this->payload['object'];

        if (! $profile) {
            return;
        }
        // TODO: Some implementations do not inline the object, skip for now
        if (! $obj || ! is_array($obj) || ! isset($obj['type'])) {
            return;
        }

        switch ($obj['type']) {
            case 'Accept':
                break;

            case 'Announce':
                if (is_array($obj) && isset($obj['object'])) {
                    $obj = $obj['object'];
                }
                if (! is_string($obj)) {
                    return;
                }
                if (Helpers::validateLocalUrl($obj)) {
                    $parsedId = last(explode('/', $obj));
                    $status = Status::find($parsedId);
                } else {
                    $status = Status::whereUri($obj)->first();
                }
                if (! $status) {
                    return;
                }
                if (AccountService::blocksDomain($status->profile_id, $profile->domain) == true) {
                    return;
                }
                FeedRemoveRemotePipeline::dispatch($status->id, $status->profile_id)->onQueue('feed');
                Status::whereProfileId($profile->id)
                    ->whereReblogOfId($status->id)
                    ->delete();
                ReblogService::removePostReblog($profile->id, $status->id);
                Notification::whereProfileId($status->profile_id)
                    ->whereActorId($profile->id)
                    ->whereAction('share')
                    ->whereItemId($status->reblog_of_id)
                    ->whereItemType('App\Status')
                    ->forceDelete();
                break;

            case 'Block':
                break;

            case 'Follow':
                $following = self::actorFirstOrCreate($obj['object']);
                if (! $following) {
                    return;
                }
                if (AccountService::blocksDomain($following->id, $profile->domain) == true) {
                    return;
                }
                Follower::whereProfileId($profile->id)
                    ->whereFollowingId($following->id)
                    ->delete();
                Notification::whereProfileId($following->id)
                    ->whereActorId($profile->id)
                    ->whereAction('follow')
                    ->whereItemId($following->id)
                    ->whereItemType('App\Profile')
                    ->forceDelete();
                FollowerService::remove($profile->id, $following->id);
                break;

            case 'Like':
                $objectUri = $obj['object'];
                if (! is_string($objectUri)) {
                    if (is_array($objectUri) && isset($objectUri['id']) && is_string($objectUri['id'])) {
                        $objectUri = $objectUri['id'];
                    } else {
                        return;
                    }
                }
                $status = Helpers::statusFirstOrFetch($objectUri);
                if (! $status) {
                    return;
                }
                if (AccountService::blocksDomain($status->profile_id, $profile->domain) == true) {
                    return;
                }
                Like::whereProfileId($profile->id)
                    ->whereStatusId($status->id)
                    ->forceDelete();
                Notification::whereProfileId($status->profile_id)
                    ->whereActorId($profile->id)
                    ->whereAction('like')
                    ->whereItemId($status->id)
                    ->whereItemType('App\Status')
                    ->forceDelete();
                break;
        }

    }

    public function handleViewActivity()
    {
        if (! isset(
            $this->payload['actor'],
            $this->payload['object']
        )) {
            return;
        }

        $actor = $this->payload['actor'];
        $obj = $this->payload['object'];

        if (! Helpers::validateUrl($actor)) {
            return;
        }

        if (! $obj || ! is_array($obj)) {
            return;
        }

        if (! isset($obj['type']) || ! isset($obj['object']) || $obj['type'] != 'Story') {
            return;
        }

        if (! Helpers::validateLocalUrl($obj['object'])) {
            return;
        }

        $profile = Helpers::profileFetch($actor);
        $storyId = Str::of($obj['object'])->explode('/')->last();

        $story = Story::whereActive(true)
            ->whereLocal(true)
            ->find($storyId);

        if (! $story) {
            return;
        }

        if (AccountService::blocksDomain($story->profile_id, $profile->domain) == true) {
            return;
        }

        if (! FollowerService::follows($profile->id, $story->profile_id)) {
            return;
        }

        $view = StoryView::firstOrCreate([
            'story_id' => $story->id,
            'profile_id' => $profile->id,
        ]);

        if ($view->wasRecentlyCreated == true) {
            $story->view_count++;
            $story->save();
        }

    }

    public function handleStoryReactionActivity()
    {
        if (! isset(
            $this->payload['actor'],
            $this->payload['id'],
            $this->payload['inReplyTo'],
            $this->payload['content']
        )) {
            return;
        }

        $id = $this->payload['id'];
        $actor = $this->payload['actor'];
        $storyUrl = $this->payload['inReplyTo'];
        $to = $this->payload['to'];
        $text = Purify::clean($this->payload['content']);

        if (parse_url($id, PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
            return;
        }

        if (! Helpers::validateUrl($id) || ! Helpers::validateUrl($actor)) {
            return;
        }

        if (! Helpers::validateLocalUrl($storyUrl)) {
            return;
        }

        if (! Helpers::validateLocalUrl($to)) {
            return;
        }

        if (Status::whereObjectUrl($id)->exists()) {
            return;
        }

        $storyId = Str::of($storyUrl)->explode('/')->last();
        $targetProfile = Helpers::profileFetch($to);

        $story = Story::whereProfileId($targetProfile->id)
            ->find($storyId);

        if (! $story) {
            return;
        }

        if ($story->can_react == false) {
            return;
        }

        $actorProfile = Helpers::profileFetch($actor);

        if (AccountService::blocksDomain($targetProfile->id, $actorProfile->domain) == true) {
            return;
        }

        if (! FollowerService::follows($actorProfile->id, $targetProfile->id)) {
            return;
        }

        $url = $id;

        if (str_ends_with($url, '/activity')) {
            $url = substr($url, 0, -9);
        }

        $status = new Status;
        $status->profile_id = $actorProfile->id;
        $status->type = 'story:reaction';
        $status->url = $url;
        $status->uri = $url;
        $status->object_url = $url;
        $status->caption = $text;
        $status->rendered = $text;
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $story->profile_id;
        $status->entities = json_encode([
            'story_id' => $story->id,
            'reaction' => $text,
        ]);
        $status->save();

        $dm = new DirectMessage;
        $dm->to_id = $story->profile_id;
        $dm->from_id = $actorProfile->id;
        $dm->type = 'story:react';
        $dm->status_id = $status->id;
        $dm->meta = json_encode([
            'story_username' => $targetProfile->username,
            'story_actor_username' => $actorProfile->username,
            'story_id' => $story->id,
            'story_media_url' => url(Storage::url($story->path)),
            'reaction' => $text,
        ]);
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $story->profile_id,
                'from_id' => $actorProfile->id,
            ],
            [
                'type' => 'story:react',
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => false,
            ]
        );

        $n = new Notification;
        $n->profile_id = $dm->to_id;
        $n->actor_id = $dm->from_id;
        $n->item_id = $dm->id;
        $n->item_type = 'App\DirectMessage';
        $n->action = 'story:react';
        $n->save();

    }

    public function handleStoryReplyActivity()
    {
        if (! isset(
            $this->payload['actor'],
            $this->payload['id'],
            $this->payload['inReplyTo'],
            $this->payload['content']
        )) {
            return;
        }

        $id = $this->payload['id'];
        $actor = $this->payload['actor'];
        $storyUrl = $this->payload['inReplyTo'];
        $to = $this->payload['to'];
        $text = Purify::clean($this->payload['content']);

        if (parse_url($id, PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
            return;
        }

        if (! Helpers::validateUrl($id) || ! Helpers::validateUrl($actor)) {
            return;
        }

        if (! Helpers::validateLocalUrl($storyUrl)) {
            return;
        }

        if (! Helpers::validateLocalUrl($to)) {
            return;
        }

        if (Status::whereObjectUrl($id)->exists()) {
            return;
        }

        $storyId = Str::of($storyUrl)->explode('/')->last();
        $targetProfile = Helpers::profileFetch($to);

        $story = Story::whereProfileId($targetProfile->id)
            ->find($storyId);

        if (! $story) {
            return;
        }

        if ($story->can_react == false) {
            return;
        }

        $actorProfile = Helpers::profileFetch($actor);

        if (AccountService::blocksDomain($targetProfile->id, $actorProfile->domain) == true) {
            return;
        }

        if (! FollowerService::follows($actorProfile->id, $targetProfile->id)) {
            return;
        }

        $url = $id;

        if (str_ends_with($url, '/activity')) {
            $url = substr($url, 0, -9);
        }

        $status = new Status;
        $status->profile_id = $actorProfile->id;
        $status->type = 'story:reply';
        $status->caption = $text;
        $status->rendered = $text;
        $status->url = $url;
        $status->uri = $url;
        $status->object_url = $url;
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $story->profile_id;
        $status->entities = json_encode([
            'story_id' => $story->id,
            'caption' => $text,
        ]);
        $status->save();

        $dm = new DirectMessage;
        $dm->to_id = $story->profile_id;
        $dm->from_id = $actorProfile->id;
        $dm->type = 'story:comment';
        $dm->status_id = $status->id;
        $dm->meta = json_encode([
            'story_username' => $targetProfile->username,
            'story_actor_username' => $actorProfile->username,
            'story_id' => $story->id,
            'story_media_url' => url(Storage::url($story->path)),
            'caption' => $text,
        ]);
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $story->profile_id,
                'from_id' => $actorProfile->id,
            ],
            [
                'type' => 'story:comment',
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => false,
            ]
        );

        $n = new Notification;
        $n->profile_id = $dm->to_id;
        $n->actor_id = $dm->from_id;
        $n->item_id = $dm->id;
        $n->item_type = 'App\DirectMessage';
        $n->action = 'story:comment';
        $n->save();

    }

    public function handleFlagActivity()
    {
        if (! isset(
            $this->payload['id'],
            $this->payload['type'],
            $this->payload['actor'],
            $this->payload['object']
        )) {
            return;
        }

        $id = $this->payload['id'];
        $actor = $this->payload['actor'];

        if (Helpers::validateLocalUrl($id) || parse_url($id, PHP_URL_HOST) !== parse_url($actor, PHP_URL_HOST)) {
            return;
        }

        $content = null;
        if (isset($this->payload['content'])) {
            if (strlen($this->payload['content']) > 5000) {
                $content = Purify::clean(substr($this->payload['content'], 0, 5000).' ... (truncated message due to exceeding max length)');
            } else {
                $content = Purify::clean($this->payload['content']);
            }
        }
        $object = $this->payload['object'];

        if (empty($object) || (! is_array($object) && ! is_string($object))) {
            return;
        }

        if (is_array($object) && count($object) > 100) {
            return;
        }

        $objects = collect([]);
        $accountId = null;

        foreach ($object as $objectUrl) {
            if (! Helpers::validateLocalUrl($objectUrl)) {
                return;
            }

            if (str_contains($objectUrl, '/users/')) {
                $username = last(explode('/', $objectUrl));
                $profileId = Profile::whereUsername($username)->first();
                if ($profileId) {
                    $accountId = $profileId->id;
                }
            } elseif (str_contains($objectUrl, '/p/')) {
                $postId = last(explode('/', $objectUrl));
                $objects->push($postId);
            } else {
                continue;
            }
        }

        if (! $accountId && ! $objects->count()) {
            return;
        }

        if ($objects->count()) {
            $obc = $objects->count();
            if ($obc > 25) {
                if ($obc > 30) {
                    return;
                } else {
                    $objLimit = $objects->take(20);
                    $objects = collect($objLimit->all());
                    $obc = $objects->count();
                }
            }
            $count = Status::whereProfileId($accountId)->find($objects)->count();
            if ($obc !== $count) {
                return;
            }
        }

        $instanceHost = parse_url($id, PHP_URL_HOST);

        $instance = Instance::updateOrCreate([
            'domain' => $instanceHost,
        ]);

        $report = new RemoteReport;
        $report->status_ids = $objects->toArray();
        $report->comment = $content;
        $report->account_id = $accountId;
        $report->uri = $id;
        $report->instance_id = $instance->id;
        $report->report_meta = [
            'actor' => $actor,
            'object' => $object,
        ];
        $report->save();

    }

    public function handleUpdateActivity()
    {
        $activity = $this->payload['object'];

        if (! isset($activity['type'], $activity['id'])) {
            return;
        }

        if (! Helpers::validateUrl($activity['id'])) {
            return;
        }

        if ($activity['type'] === 'Note') {
            if (Status::whereObjectUrl($activity['id'])->exists()) {
                StatusRemoteUpdatePipeline::dispatch($activity);
            }
        } elseif ($activity['type'] === 'Person') {
            if (UpdatePersonValidator::validate($this->payload)) {
                HandleUpdateActivity::dispatch($this->payload)->onQueue('low');
            }
        }
    }

    public function handleMoveActivity()
    {
        $actor = $this->payload['actor'];
        $activity = $this->payload['object'];
        $target = $this->payload['target'];
        if (
            ! Helpers::validateUrl($actor) ||
            ! Helpers::validateUrl($activity) ||
            ! Helpers::validateUrl($target)
        ) {
            return;
        }

        Bus::chain([
            new ProcessMovePipeline($target, $activity),
            new MoveMigrateFollowersPipeline($target, $activity),
            new UnfollowLegacyAccountMovePipeline($target, $activity),
            new CleanupLegacyAccountMovePipeline($target, $activity),
        ])
            ->catch(function (Throwable $e) {
                Log::error($e);
            })
            ->onQueue('move')
            ->delay(now()->addMinutes(random_int(1, 3)))
            ->dispatch();
    }
}
