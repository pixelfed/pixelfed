<?php

namespace App\Jobs\VideoPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Cache;
use FFMpeg;
use Storage;
use App\Jobs\MediaPipeline\MediaStoragePipeline;
use App\Media;
use App\Services\MediaService;
use App\Services\StatusService;

class VideoPostProcess implements ShouldQueue
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
        if($media->mime != 'video/quicktime') {
            return;
        }
        $base = $media->media_path;
        $path = explode('/', $base);
        $name = last($path);
        try {
            $t = explode('.', $name);
            $t = $t[0].'.mp4';
            $i = count($path) - 1;
            $path[$i] = $t;
            $save = implode('/', $path);
            FFMpeg::open($base)
                ->export()
                ->toDisk('local')
                ->addFilter('-codec', 'copy', '-map_metadata', '0', '-movflags', '+faststart')
                ->save($save);
            $media->media_path = $save;
            $media->mime = 'video/mp4';

            $local = Storage::disk('local');
            $media->size = $local->size($save);
            $media->save();

            $local->delete($base);
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
