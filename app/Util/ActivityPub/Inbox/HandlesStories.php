<?php

namespace App\Util\ActivityPub\Inbox;

use App\DirectMessage;
use App\Jobs\StoryPipeline\StoryFetch;
use App\Models\Conversation;
use App\Services\FollowerService;
use App\Services\NotificationService;
use App\Services\SanitizeService;
use App\Services\StoryIndexService;
use App\Status;
use App\Story;
use App\StoryView;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HandlesStories
{
    public function handleAddActivity(): void
    {
        if (! $this->payloadHasKeys(['actor', 'object'])) {
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

        if ($obj['type'] === 'Story') {
            StoryFetch::dispatch($this->payload);
        }
    }

    public function handleViewActivity(): void
    {
        if (! $this->payloadHasKeys(['actor', 'object'])) {
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

        if ($this->isDomainBlocked($story->profile_id, $profile->domain)) {
            return;
        }

        if (! FollowerService::follows($profile->id, $story->profile_id)) {
            return;
        }

        $view = StoryView::firstOrCreate([
            'story_id' => $story->id,
            'profile_id' => $profile->id,
        ]);

        $index = app(StoryIndexService::class);
        $index->markSeen($profile->id, $story->profile_id, $story->id, $story->created_at);

        if ($view->wasRecentlyCreated == true) {
            $story->view_count++;
            $story->save();
        }
    }

    public function handleStoryReactionActivity(): void
    {
        $this->handleStoryInteraction('story:reaction', 'story:react');
    }

    public function handleStoryReplyActivity(): void
    {
        $this->handleStoryInteraction('story:reply', 'story:comment');
    }

    /**
     * Shared handler for story reactions and replies.
     *
     * @param  string  $statusType  The status type (story:reaction or story:reply)
     * @param  string  $dmType  The DM type (story:react or story:comment)
     */
    protected function handleStoryInteraction(string $statusType, string $dmType): void
    {
        if (! $this->payloadHasKeys(['actor', 'id', 'inReplyTo', 'content'])) {
            return;
        }

        $id = $this->payload['id'];
        $actor = $this->payload['actor'];
        $storyUrl = $this->payload['inReplyTo'];
        $to = $this->payload['to'];
        $text = app(SanitizeService::class)->html($this->payload['content']);

        if (! $this->hostsMatch($id, $actor)) {
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

        $story = Story::whereProfileId($targetProfile->id)->find($storyId);

        if (! $story) {
            return;
        }

        if ($story->can_react == false) {
            return;
        }

        $actorProfile = Helpers::profileFetch($actor);

        if ($this->isDomainBlocked($targetProfile->id, $actorProfile->domain)) {
            return;
        }

        if (! FollowerService::follows($actorProfile->id, $targetProfile->id)) {
            return;
        }

        $url = $this->stripActivitySuffix($id);

        $entitiesKey = $statusType === 'story:reaction' ? 'reaction' : 'caption';
        $metaKey = $statusType === 'story:reaction' ? 'reaction' : 'caption';

        $status = new Status;
        $status->profile_id = $actorProfile->id;
        $status->type = $statusType;
        $status->url = $url;
        $status->uri = $url;
        $status->object_url = $url;
        $status->caption = $text;
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $story->profile_id;
        $status->entities = json_encode([
            'story_id' => $story->id,
            $entitiesKey => $text,
        ]);
        $status->save();

        $dm = new DirectMessage;
        $dm->to_id = $story->profile_id;
        $dm->from_id = $actorProfile->id;
        $dm->type = $dmType;
        $dm->status_id = $status->id;
        $dm->meta = json_encode([
            'story_username' => $targetProfile->username,
            'story_actor_username' => $actorProfile->username,
            'story_id' => $story->id,
            'story_media_url' => url(Storage::url($story->path)),
            $metaKey => $text,
        ]);
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $story->profile_id,
                'from_id' => $actorProfile->id,
            ],
            [
                'type' => $dmType,
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => false,
            ]
        );

        NotificationService::createNotification($dm->to_id, $dm->from_id, $dmType, $dm->id, DirectMessage::class);
    }
}
