<?php

namespace App\Jobs\GroupsPipeline;

use App\Models\GroupMedia;
use App\Util\Media\Image;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;
use Illuminate\Http\File;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Exception\ConnectException;
use League\Flysystem\UnableToWriteFile;

class ImageS3UploadPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $media;
    static $attempts = 1;

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

        if(!$media || (bool) config_cache('pixelfed.cloud_storage') === false) {
            return;
        }

        $path = storage_path('app/' . $media->media_path);

        $p = explode('/', $media->media_path);
        $name = array_pop($p);
        $storagePath = implode('/', $p);

        $url =  (bool) config_cache('pixelfed.cloud_storage') && (bool) config('media.storage.remote.resilient_mode') ?
            self::handleResilientStore($storagePath, $path, $name) :
            self::handleStore($storagePath, $path, $name);

        if($url && strlen($url) && str_starts_with($url, 'https://')) {
            $media->cdn_url = $url;
            $media->processed_at = now();
            $media->version = 11;
            $media->save();
            Storage::disk('local')->delete($media->media_path);
        }
    }

    protected function handleStore($storagePath, $path, $name)
    {
        return retry(3, function() use($storagePath, $path, $name) {
            $baseDisk = (bool) config_cache('pixelfed.cloud_storage') ? config('filesystems.cloud') : 'local';
            $disk = Storage::disk($baseDisk);
            $file = $disk->putFileAs($storagePath, new File($path), $name, 'public');
            return $disk->url($file);
        }, random_int(100, 500));
    }

    protected function handleResilientStore($storagePath, $path, $name)
    {
        $attempts = 0;
        return retry(4, function() use($storagePath, $path, $name, $attempts) {
            self::$attempts++;
            usleep(100000);
            $baseDisk = self::$attempts > 1 ? $this->getAltDriver() : config('filesystems.cloud');
            try {
                $disk = Storage::disk($baseDisk);
                $file = $disk->putFileAs($storagePath, new File($path), $name, 'public');
            } catch (S3Exception | ClientException | ConnectException | UnableToWriteFile | Exception $e) {}
            return $disk->url($file);
        }, function (int $attempt, Exception $exception) {
            return $attempt * 200;
        });
    }

    protected function getAltDriver()
    {
        return config('filesystems.cloud');
    }
}
