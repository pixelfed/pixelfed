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
use GuzzleHttp\Client;

class MediaStorageService {

	public static function store(Media $media)
	{
		if(config('pixelfed.cloud_storage') == true) {
			(new self())->cloudStore($media);
		}

		return;
	}

	public static function head($url)
	{
		$c = new Client();
		$r = $c->request('HEAD', $url);
		$h = $r->getHeaders();
		return [
			'length' => $h['Content-Length'][0],
			'mime' => $h['Content-Type'][0]
		];
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
		$media->replicated_at = now();
		$media->save();
		if($media->status_id) {
			Cache::forget('status:transformer:media:attachments:' . $media->status_id);
		}
	}

	protected function remoteToCloud($media)
	{
		$url = $media->remote_url;

		if(!Helpers::validateUrl($url)) {
			return;
		}

		$head = $this->head($media->remote_url);
		$mimes = [
			'image/jpeg',
			'image/png',
			'video/mp4'
		];

		$mime = $head['mime'];
		$max_size = (int) config('pixelfed.max_photo_size') * 1000;
		$media->size = $head['length'];
		$media->remote_media = true;
		$media->save();

		if(!in_array($mime, $mimes)) {
			return;
		}

		if($head['length'] == $max_size) {
			return;
		}

		$ext = $mime == 'image/jpeg' ? '.jpg' : ($mime == 'image/png' ? '.png' : 'mp4');

		$base = MediaPathService::get($media->profile);
		$path = Str::random(40) . $ext;
		$tmpBase = storage_path('app/remcache/');
		$tmpPath = $media->profile_id . '-' . $path;
		$tmpName = $tmpBase . $tmpPath;
		$data = file_get_contents($url, false, null, 0, $head['length']);
		file_put_contents($tmpName, $data);
		$hash = hash_file('sha256', $tmpName);

		$disk = Storage::disk(config('filesystems.cloud'));
		$file = $disk->putFileAs($base, new File($tmpName), $path, 'public');
		$permalink = $disk->url($file);
		$media->cdn_url = $permalink;
		$media->original_sha256 = $hash;
		$media->replicated_at = now();
		$media->save();

		unlink($tmpName);

	}
}