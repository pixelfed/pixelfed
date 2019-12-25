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

        } catch (Exception $e) {
            
        }

        if(config('pixelfed.cloud_storage') == true) {
            $path = storage_path('app/'.$media->media_path);
            $thumb = storage_path('app/'.$media->thumbnail_path);
            $p = explode('/', $media->media_path);
            $monthHash = $p[2];
            $userHash = $p[3];
            $storagePath = "public/m/{$monthHash}/{$userHash}";
            $file = Storage::disk(config('filesystems.cloud'))->putFile($storagePath, new File($path), 'public');
            $url = Storage::disk(config('filesystems.cloud'))->url($file);
            $thumbFile = Storage::disk(config('filesystems.cloud'))->putFile($storagePath, new File($thumb), 'public');
            $thumbUrl = Storage::disk(config('filesystems.cloud'))->url($thumbFile);
            $media->thumbnail_url = $thumbUrl;
            $media->cdn_url = $url;
            $media->optimized_url = $url;
            $media->save();

        }
        
        if($media->status_id) {
            Cache::forget('status:transformer:media:attachments:' . $media->status_id);
        }
    }
}
