<?php

namespace App\Jobs\MediaPipeline;

use App\Media;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Services\Media\MediaHlsService;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class MediaDeletePipeline implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $media;

    public $timeout = 300;
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
        return 'media:purge-job:id-' . $this->media->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("media:purge-job:id-{$this->media->id}"))->shared()->dontRelease()];
    }

	public function __construct(Media $media)
	{
		$this->media = $media;
	}

	public function handle()
	{
		$media = $this->media;
		$path = $media->media_path;
		$thumb = $media->thumbnail_path;

		if(!$path) {
			return 1;
		}

		$e = explode('/', $path);
		array_pop($e);
		$i = implode('/', $e);

		if(config_cache('pixelfed.cloud_storage') == true) {
			$disk = Storage::disk(config('filesystems.cloud'));

			if($path && $disk->exists($path)) {
				$disk->delete($path);
			}

			if($thumb && $disk->exists($thumb)) {
				$disk->delete($thumb);
			}
		}

		$disk = Storage::disk(config('filesystems.local'));

		if($path && $disk->exists($path)) {
			$disk->delete($path);
		}

		if($thumb && $disk->exists($thumb)) {
			$disk->delete($thumb);
		}

		if($media->hls_path != null) {
            $files = MediaHlsService::allFiles($media);
            if($files && count($files)) {
                foreach($files as $file) {
                    $disk->delete($file);
                }
            }
		}

		$media->delete();

		return 1;
	}
}
