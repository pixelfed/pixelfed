<?php

namespace App\Jobs\ImageOptimizePipeline;

use Storage;
use App\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ImageOptimizer;
use Illuminate\Http\File;

class ImageUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

    protected $protectedMimes = [
        'image/jpeg',
        'image/png',
    ];

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
        if(!$media) {
            return;
        }
        $path = storage_path('app/'.$media->media_path);
        $thumb = storage_path('app/'.$media->thumbnail_path);
        
        if (!is_file($path)) {
            return;
        }

        if (in_array($media->mime, $this->protectedMimes) == true) {
            ImageOptimizer::optimize($thumb);
            ImageOptimizer::optimize($path);
        }

        if (!is_file($path) || !is_file($thumb)) {
            return;
        }

        $photo_size = filesize($path);
        $thumb_size = filesize($thumb);
        $total = ($photo_size + $thumb_size);
        $media->size = $total;
        $media->save();

        if(config('pixelfed.cloud_storage') == true) {
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
    }
}
