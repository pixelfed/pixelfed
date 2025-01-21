<?php

namespace App\Jobs\GroupsPipeline;

use App\Models\GroupMedia;
use App\Util\Media\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Storage;
use Image as Intervention;

class ImageResizePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;

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
    public function __construct(GroupMedia $media)
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

        if (!Storage::exists($media->media_path) || $media->skip_optimize) {
            return;
        }

        $path = $media->media_path;
        $file = storage_path('app/' . $path);
        $quality = config_cache('pixelfed.image_quality');

        $orientations = [
            'square' => [
                'width'  => 1080,
                'height' => 1080,
            ],
            'landscape' => [
                'width'  => 1920,
                'height' => 1080,
            ],
            'portrait' => [
                'width'  => 1080,
                'height' => 1350,
            ],
        ];

        try {
            $img = Intervention::make($file);
            $img->orientate();
            $width = $img->width();
            $height = $img->height();
            $aspect = $width / $height;
            $orientation = $aspect === 1 ? 'square' : ($aspect > 1 ? 'landscape' : 'portrait');
            $ratio = $orientations[$orientation];
            $img->resize($ratio['width'], $ratio['height']);
            $img->save($file, $quality);
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
