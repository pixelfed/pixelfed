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
use App\Util\Media\Instagraph as Instagraph;
use Illuminate\Http\File;

class ImageUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 240;
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
        $path = storage_path('app/'.$media->media_path);
        $thumb = storage_path('app/'.$media->thumbnail_path);

        if (in_array($media->mime, $this->protectedMimes) == true) {
            ImageOptimizer::optimize($thumb);
            $this->filter($thumb, $media->filter_name);
            ImageOptimizer::optimize($path);
            $this->filter($path, $media->filter_name);
        }

        if (!is_file($path) || !is_file($thumb)) {
            return;
        }

        $photo_size = filesize($path);
        $thumb_size = filesize($thumb);
        //$orig_size = filesize($orig_path);
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
            $media->cdn_url = $url;
            $media->optimized_url = $url;
            $media->save();
        }
    }

    public function filter($img, $filtername) {
        $ig = new Instagraph($img, $img);
        switch($filtername) {
            case '1977':
                $ig->nineteenseventyseven();
                break;
            case 'Aden':
                $ig->aden();
                break;
            case 'Amaro':
                $ig->amaro();
                break;
            case 'Ashby':
                $ig->ashby();
                break;
            case 'Brannan':
                $ig->brannan();
                break;
            case 'Brooklyn':
                $ig->brooklyn();
                break;
            case 'Charmes':
                $ig->charmes();
                break;
            case 'Clarendon':
                $ig->clarendon();
                break;
            case 'Crema':
                $ig->crema();
                break;
            case 'Dogpatch':
                $ig->dogpatch();
                break;
            case 'Earlybird':
                $ig->earlybird();
                break;
            case 'Gingham':
                $ig->gingham();
                break;
            case 'Ginza':
                $ig->ginza();
                break;
            case 'Hefe':
                $ig->hefe();
                break;
            case 'Helena':
                $ig->helena();
                break;
            case 'Hudson':
                $ig->hudson();
                break;
            case 'Inkwell':
                $ig->inkwell();
                break;
            case 'Juno':
                $ig->juno();
                break;
            case 'Kelvin':
                $ig->kelvin();
                break;
            case 'Lark':
                $ig->lark();
                break;
            case 'Lo-Fi':
                $ig->lofi();
                break;
            case 'Ludwig':
                $ig->ludwig();
                break;
            case 'Maven':
                $ig->maven();
                break;
            case 'Mayfair':
                $ig->mayfair();
                break;
            case 'Moon':
                $ig->moon();
                break;
            case 'Nashville':
                $ig->nashville();
                break;
            case 'Perpetua':
                $ig->perpetua();
                break;
            case 'Poprocket':
                $ig->poprocket();
                break;
            case 'Reyes':
                $ig->reyes();
                break;
            case 'Rise':
                $ig->rise();
                break;
            case 'Sierra':
                $ig->sierra();
                break;
            case 'Skyline':
                $ig->skyline();
                break;
            case 'Slumber':
                $ig->slumber();
                break;
            case 'Stinson':
                $ig->stinson();
                break;
            case 'Sutro':
                $ig->sutro();
                break;
            case 'Toaster':
                $ig->toaster();
                break;
            case 'Valencia':
                $ig->valencia();
                break;
            case 'Vesper':
                $ig->vesper();
                break;
            case 'Willow':
                $ig->willow();
                break;
            case 'X-Pro II':
                $ig->xpro();
                break;
        }
    }
}
