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

class MediaDeletePipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $media;

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
			$disk->delete($path);
			$disk->delete($thumb);

			if(count($e) > 4 && count($disk->files($i)) == 0) {
				$disk->deleteDirectory($i);
			}
		}

		$disk = Storage::disk(config('filesystems.local'));
		if($disk->exists($path)) {
			$disk->delete($path);
		}
		if($disk->exists($thumb)) {
			$disk->delete($thumb);
		}
		if(count($e) > 4 && count($disk->files($i)) == 0) {
			$disk->deleteDirectory($i);
		}

		$media->forceDelete();

		return;
	}

}
