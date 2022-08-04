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

class VideoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

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
            ->getFrameFromSeconds(0)
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
