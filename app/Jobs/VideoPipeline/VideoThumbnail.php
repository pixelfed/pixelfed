<?php

namespace App\Jobs\VideoPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Cache;
use FFMpeg;
use Storage;
use App\Media;
use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Util\Media\Blurhash;
use App\Services\MediaService;
use App\Services\StatusService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class VideoThumbnail implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    public $timeout = 900;
    public $tries = 3;
    public $maxExceptions = 1;
    public $failOnTimeout = true;
    public $deleteWhenMissingModels = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'media:video-thumb:id-' . $this->media->id;
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
        if($media->mime != 'video/mp4') {
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

            $blurhash = Blurhash::generate($media);
            if($blurhash) {
                $media->blurhash = $blurhash;
                $media->save();
            }

            if(config('media.hls.enabled')) {
                VideoHlsPipeline::dispatch($media)->onQueue('mmo');
            }
        } catch (Exception $e) {
            
        }

        if($media->status_id) {
            Cache::forget('status:transformer:media:attachments:' . $media->status_id);
            MediaService::del($media->status_id);
            Cache::forget('status:thumb:nsfw0' . $media->status_id);
            Cache::forget('status:thumb:nsfw1' . $media->status_id);
            Cache::forget('pf:services:sh:id:' . $media->status_id);
            StatusService::del($media->status_id);
        }

        MediaStoragePipeline::dispatch($media);
    }
}
