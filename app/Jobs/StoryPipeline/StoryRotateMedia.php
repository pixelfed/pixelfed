<?php

namespace App\Jobs\StoryPipeline;

use App\Services\StoryIndexService;
use App\Story;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StoryRotateMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $story;

    protected $newPath;

    protected $oldPath;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 300;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

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
        try {
            $story = $this->story->fresh();

            if (! $story) {
                if (config('app.dev_log')) {
                    Log::warning('StoryRotateMedia: Story not found', ['story_id' => $this->story->id]);
                }

                return;
            }

            if ($story->local == false) {
                return;
            }

            $this->oldPath = $story->path;
            $this->newPath = $this->generateNewPath($this->oldPath);

            if (! Storage::exists($this->oldPath)) {
                if (config('app.dev_log')) {
                    Log::warning('StoryRotateMedia: Original file not found', [
                        'story_id' => $story->id,
                        'path' => $this->oldPath,
                    ]);
                }

                return;
            }

            $this->rotateMedia($story);

            if (config('app.dev_log')) {
                Log::info('StoryRotateMedia: Successfully rotated media', [
                    'story_id' => $story->id,
                    'old_path' => $this->oldPath,
                    'new_path' => $this->newPath,
                ]);
            }

        } catch (Exception $e) {
            if (config('app.dev_log')) {
                Log::error('StoryRotateMedia: Job failed', [
                    'story_id' => $this->story->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            throw $e;
        }
    }

    /**
     * Handle the media rotation process
     */
    protected function rotateMedia(Story $story)
    {
        DB::transaction(function () use ($story) {
            if (! Storage::copy($this->oldPath, $this->newPath)) {
                throw new Exception("Failed to copy file from {$this->oldPath} to {$this->newPath}");
            }

            if (! Storage::exists($this->newPath)) {
                throw new Exception("New file was not created at {$this->newPath}");
            }

            $story->path = $this->newPath;
            $story->bearcap_token = null;

            if (! $story->save()) {
                throw new Exception('Failed to update story record in database');
            }

            if (! Storage::delete($this->oldPath)) {
                if (config('app.dev_log')) {
                    Log::warning('StoryRotateMedia: Failed to delete old file', [
                        'story_id' => $story->id,
                        'path' => $this->oldPath,
                    ]);
                }
            }
        });

        $this->updateSearchIndex($story);
    }

    /**
     * Update the search index
     */
    protected function updateSearchIndex(Story $story)
    {
        try {
            $index = app(StoryIndexService::class);

            $index->removeStory($story->id, $story->profile_id);

            usleep(random_int(100000, 500000));

            $index->indexStory($story);

        } catch (Exception $e) {
            if (config('app.dev_log')) {
                Log::error('StoryRotateMedia: Failed to update search index', [
                    'story_id' => $story->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generate a new path for the rotated media
     */
    protected function generateNewPath(string $oldPath): string
    {
        $paths = explode('/', $oldPath);
        $name = array_pop($paths);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $new = Str::random(13).'_'.Str::random(24).'_'.Str::random(3).'.'.$ext;
        array_push($paths, $new);

        return implode('/', $paths);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception)
    {
        if (config('app.dev_log')) {
            Log::error('StoryRotateMedia: Job permanently failed', [
                'story_id' => $this->story->id,
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
            ]);
        }

        if ($this->newPath && Storage::exists($this->newPath)) {
            try {
                Storage::delete($this->newPath);
                if (config('app.dev_log')) {
                    Log::info('StoryRotateMedia: Cleaned up orphaned file', [
                        'path' => $this->newPath,
                    ]);
                }
            } catch (Exception $e) {
                if (config('app.dev_log')) {
                    Log::error('StoryRotateMedia: Failed to cleanup orphaned file', [
                        'path' => $this->newPath,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil()
    {
        return now()->addHours(2);
    }
}
