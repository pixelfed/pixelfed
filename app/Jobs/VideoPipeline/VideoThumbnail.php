<?php

namespace App\Jobs\VideoPipeline;

use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Models\Media;
use App\Services\MediaService;
use App\Services\StatusService;
use App\Util\Media\Blurhash;
use FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Timeout(900)]
#[Tries(3)]
#[MaxExceptions(1)]
#[FailOnTimeout]
#[DeleteWhenMissingModels]
#[UniqueFor(3600)]
class VideoThumbnail implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'media:video-thumb:id-'.$this->media->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("media:video-thumb:id-{$this->media->id}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $media = $this->media;
        if ($media->mime != 'video/mp4') {
            return;
        }
        $base = $media->media_path;
        $path = explode('/', $base);
        $name = last($path);
        try {
            $t = explode('.', $name);
            $t = $t[0].'_thumb.jpeg';
            $i = count($path) - 1;
            $path[$i] = $t;
            $save = implode('/', $path);
            $video = FFMpeg::open($base)
                ->getFrameFromSeconds(1)
                ->export()
                ->toDisk('local')
                ->save($save);

            $media->thumbnail_path = $save;
            $media->save();

            // Isolated from the thumbnail work on purpose. The blurhash is decorative;
            // MediaStoragePipeline at the end of this method is not, and until now any
            // failure here skipped it, leaving the video on local disk forever with no
            // failed_jobs row to show for it (pixelfed#2652).
            try {
                $blurhash = Blurhash::generate($media);
                if ($blurhash) {
                    $media->blurhash = $blurhash;
                    $media->save();
                }
            } catch (\Throwable $e) {
                // \Throwable, not \Exception: the failure this guard exists for is a
                // memory_limit exhaustion, which surfaces as \Error (or a fatal), not
                // \Exception. Catching only \Exception here would let the very case
                // that strands the video (pixelfed#2652) slip through and skip the
                // MediaStoragePipeline dispatch below.
                if (config('app.dev_log')) {
                    Log::error('Video blurhash generation failed: '.$e->getMessage());
                }
            }

            if (config('media.hls.enabled')) {
                VideoHlsPipeline::dispatch($media)->onQueue('mmo');
            }
        } catch (\Exception $e) {
            if (config('app.dev_log')) {
                Log::error('Video thumbnail generation failed: '.$e->getMessage());
            }

            throw $e;
        }

        if ($media->status_id) {
            Cache::forget('status:transformer:media:attachments:'.$media->status_id);
            MediaService::del($media->status_id);
            Cache::forget('status:thumb:nsfw0'.$media->status_id);
            Cache::forget('status:thumb:nsfw1'.$media->status_id);
            Cache::forget('pf:services:sh:id:'.$media->status_id);
            StatusService::del($media->status_id);
        }

        MediaStoragePipeline::dispatch($media);
    }
}
