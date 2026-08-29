<?php

namespace App\Jobs\StoryPipeline;

use App\Models\Story;
use App\Services\ActivityPubDeliveryService;
use App\Services\FollowerService;
use App\Services\FractalService;
use App\Services\StoryIndexService;
use App\Services\StoryService;
use App\Transformer\ActivityPub\Verb\DeleteStory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StoryExpire implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $story;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Story $story)
    {
        $this->story = $story;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $story = $this->story;

        if ($story->local == false) {
            $this->handleRemoteExpiry();

            return;
        }

        if ($story->active == false) {
            return;
        }

        if ($story->expires_at->gt(now())) {
            return;
        }

        $story->active = false;
        $story->save();

        $this->rotateMediaPath();

        $index = app(StoryIndexService::class);
        $index->removeStory($story->id, $story->profile_id);

        $this->fanoutExpiry();

        StoryService::delLatest($story->profile_id);
    }

    protected function rotateMediaPath()
    {
        $story = $this->story;
        $date = date('Y').date('m');
        $old = $story->path;
        $base = "story_archives/{$story->profile_id}/{$date}/";
        $paths = explode('/', $old);
        $path = array_pop($paths);
        $newPath = $base.$path;

        // Archive on the same disk the story media lives on. When the instance
        // is configured for cloud storage this is S3, and $disk->move() is
        // performed by the Flysystem S3 adapter (server-side copy + delete).
        $disk = config('filesystems.default') === 'local'
            ? Storage::disk('local')
            : Storage::disk(config('filesystems.default'));

        if (! $disk->exists($old)) {
            return;
        }

        try {
            $disk->move($old, $newPath);
        } catch (\Throwable $e) {
            Log::error('StoryExpire: failed to archive story media', [
                'story_id' => $story->id,
                'from' => $old,
                'to' => $newPath,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $story->bearcap_token = null;
        $story->path = $newPath;
        $story->save();

        $dir = implode('/', $paths);
        $remainingFiles = $disk->files($dir);
        if (empty($remainingFiles)) {
            $disk->deleteDirectory($dir);
        }
    }

    protected function fanoutExpiry()
    {
        $story = $this->story;
        $profile = $story->profile;

        if ($story->local == false || $story->remote_url) {
            return;
        }

        $audience = FollowerService::softwareAudience($story->profile_id, 'pixelfed');

        if (empty($audience)) {
            return;
        }

        $activity = FractalService::item($story, new DeleteStory);

        ActivityPubDeliveryService::pool($profile, $audience, $activity);
    }

    protected function handleRemoteExpiry()
    {
        $story = $this->story;
        $story->active = false;
        $story->save();

        $index = app(StoryIndexService::class);
        $index->removeStory($story->id, $story->profile_id);

        $path = $story->path;

        $disk = config('filesystems.default') === 'local'
            ? Storage::disk('local')
            : Storage::disk(config('filesystems.default'));

        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        $story->views()->delete();
        $story->delete();
    }
}
