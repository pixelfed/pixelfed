<?php

namespace App\Jobs\GroupPipeline;

use App\Media;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use App\Services\MediaStorageService;

class GroupMediaPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $media;

	public function __construct(Media $media)
	{
		$this->media = $media;
	}

	public function handle()
	{
		MediaStorageService::store($this->media);
	}

	protected function localToCloud($media)
	{
		$path = storage_path('app/'.$media->media_path);
		$thumb = storage_path('app/'.$media->thumbnail_path);

		$p = explode('/', $media->media_path);
		$name = array_pop($p);
		$pt = explode('/', $media->thumbnail_path);
		$thumbname = array_pop($pt);
		$storagePath = implode('/', $p);

		$disk = Storage::disk(config('filesystems.cloud'));
		$file = $disk->putFileAs($storagePath, new File($path), $name, 'public');
		$url = $disk->url($file);
		$thumbFile = $disk->putFileAs($storagePath, new File($thumb), $thumbname, 'public');
		$thumbUrl = $disk->url($thumbFile);
		$media->thumbnail_url = $thumbUrl;
		$media->cdn_url = $url;
		$media->optimized_url = $url;
		$media->replicated_at = now();
		$media->save();
		if($media->status_id) {
			Cache::forget('status:transformer:media:attachments:' . $media->status_id);
		}
	}

}
