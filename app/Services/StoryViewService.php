<?php

namespace App\Services;

use App\Models\Story;
use App\Models\StoryView;

class StoryViewService
{
    public function __construct(protected StoryIndexService $index) {}

    public function record(Story $story, int $viewerId): bool
    {
        if ((int) $story->profile_id === $viewerId) {
            return false;
        }

        $view = StoryView::firstOrCreate([
            'story_id' => $story->id,
            'profile_id' => $viewerId,
        ]);

        $this->index->markSeen($viewerId, (int) $story->profile_id, (int) $story->id, $story->created_at);
        StoryService::addSeen($viewerId, $story->id);

        if (! $view->wasRecentlyCreated) {
            return false;
        }

        Story::whereKey($story->id)->increment('view_count');
        $this->index->incrementViewCount($story->id);

        return true;
    }
}
