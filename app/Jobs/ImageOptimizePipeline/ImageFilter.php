<?php
namespace App\Jobs\ImageOptimizePipeline;

use Storage;
use App\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\File;

class ImageFilter implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 240;
    protected $media;

    public function __construct(Media $media)
    {
        $this->media = $media;
    }

    public function handle()
    {
        $this->filter($this->media->media_path, $this->media->filter_name);
        $this->filter($this->media->thumbnail_path, $this->media->filter_name);
    }

    public function filter($img, $filtername) {
        preg_match('/\/([a-zA-Z0-9_\-]+).([a-z]+)$/', $img, $matches);
        try {
            $f = str_replace("-", "_", str_replace(" ", "_", strtolower($filtername)));
            if (!file_exists('tmp/'.$matches[1])) {
                exec('mkdir tmp/'.$matches[1]);
            }
            exec('cd tmp/'.$matches[1].' && rustagram '.storage_path('app/'.$img).' '.$f.'&& cp output.jpg '.storage_path('app/'.$img).' && cd ../ && rm -rf '.$matches[1]);
        } catch(Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}
