<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Media;
use App\Profile;
use App\User;

class MediaStorageService {

	public static function store(Media $media)
	{
		if(config('pixelfed.cloud_storage') == true) {
			(new self())->cloudStore($media);
		}

		return;
	}

	protected function cloudStore($media)
	{
		if($media->remote_media == true) {
			(new self())->remoteToCloud($media);
		} else {
			(new self())->localToCloud($media);
		}
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
		$media->save();
		if($media->status_id) {
			Cache::forget('status:transformer:media:attachments:' . $media->status_id);
		}
	}

	protected function remoteToCloud($media)
	{
		// todo
	}
}