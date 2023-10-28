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
use App\Services\AccountService;
use App\Http\Controllers\AvatarController;
use GuzzleHttp\Exception\RequestException;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use Illuminate\Support\Arr;
use App\Jobs\AvatarPipeline\AvatarStorageCleanup;

class MediaStorageService {

	public static function store(Media $media)
	{
		if(config_cache('pixelfed.cloud_storage') == true) {
			(new self())->cloudStore($media);
		}

		return;
	}

	public static function avatar($avatar, $local = false, $skipRecentCheck = false)
	{
		return (new self())->fetchAvatar($avatar, $local, $skipRecentCheck);
	}

	public static function head($url)
	{
		$c = new Client();
		try {
			$r = $c->request('HEAD', $url);
		} catch (RequestException $e) {
			return false;
		}

        $h = Arr::mapWithKeys($r->getHeaders(), function($item, $key) {
            return [strtolower($key) => last($item)];
        });

        if(!isset($h['content-length'], $h['content-type'])) {
            return false;
        }

        $len = (int) $h['content-length'];
        $mime = $h['content-type'];

		if($len < 10 || $len > ((config_cache('pixelfed.max_photo_size') * 1000))) {
			return false;
		}

		return [
			'length' => $len,
			'mime' => $mime
		];
	}

	protected function cloudStore($media)
	{
		if($media->remote_media == true) {
			if(config('media.storage.remote.cloud')) {
				(new self())->remoteToCloud($media);
			}
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

		$url = ResilientMediaStorageService::store($storagePath, $path, $name);
		if($thumb) {
			$thumbUrl = ResilientMediaStorageService::store($storagePath, $thumb, $thumbname);
			$media->thumbnail_url = $thumbUrl;
		}
		$media->cdn_url = $url;
		$media->optimized_url = $url;
		$media->replicated_at = now();
		$media->save();
		if($media->status_id) {
			Cache::forget('status:transformer:media:attachments:' . $media->status_id);
			MediaService::del($media->status_id);
			StatusService::del($media->status_id, false);
		}
	}

	protected function remoteToCloud($media)
	{
		$url = $media->remote_url;

		if(!Helpers::validateUrl($url)) {
			return;
		}

		$head = $this->head($media->remote_url);

		if(!$head) {
			return;
		}

		$mimes = [
			'image/jpeg',
			'image/png',
			'video/mp4'
		];

		$mime = $head['mime'];
		$max_size = (int) config_cache('pixelfed.max_photo_size') * 1000;
		$media->size = $head['length'];
		$media->remote_media = true;
		$media->save();

		if(!in_array($mime, $mimes)) {
			return;
		}

		if($head['length'] >= $max_size) {
			return;
		}

		switch ($mime) {
			case 'image/png':
				$ext = '.png';
				break;

			case 'image/gif':
				$ext = '.gif';
				break;

			case 'image/jpeg':
				$ext = '.jpg';
				break;

			case 'video/mp4':
				$ext = '.mp4';
				break;
		}

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

		$media->media_path = $file;
		$media->cdn_url = $permalink;
		$media->original_sha256 = $hash;
		$media->replicated_at = now();
		$media->save();

		if($media->status_id) {
			Cache::forget('status:transformer:media:attachments:' . $media->status_id);
		}

		unlink($tmpName);
	}

	protected function fetchAvatar($avatar, $local = false, $skipRecentCheck = false)
	{
		$queue = random_int(1, 15) > 5 ? 'mmo' : 'low';
		$url = $avatar->remote_url;
		$driver = $local ? 'local' : config('filesystems.cloud');

		if(empty($url) || Helpers::validateUrl($url) == false) {
			return;
		}

		$head = $this->head($url);

		if($head == false) {
			return;
		}

		$mimes = [
			'application/octet-stream',
			'image/jpeg',
			'image/png',
		];

		$mime = $head['mime'];
		$max_size = (int) config('pixelfed.max_avatar_size') * 1000;

		if(!$skipRecentCheck) {
			if($avatar->last_fetched_at && $avatar->last_fetched_at->gt(now()->subMonths(3))) {
				return;
			}
		}

		Cache::forget('avatar:' . $avatar->profile_id);
		AccountService::del($avatar->profile_id);

		// handle pleroma edge case
		if(Str::endsWith($mime, '; charset=utf-8')) {
			$mime = str_replace('; charset=utf-8', '', $mime);
		}

		if(!in_array($mime, $mimes)) {
			return;
		}

		if($head['length'] >= $max_size) {
			return;
		}

		$base = ($local ? 'public/cache/' : 'cache/') . 'avatars/' . $avatar->profile_id;
		$ext = $head['mime'] == 'image/jpeg' ? 'jpg' : 'png';
		$path = 'avatar_' . strtolower(Str::random(random_int(3,6))) . '.' . $ext;
		$tmpBase = storage_path('app/remcache/');
		$tmpPath = 'avatar_' . $avatar->profile_id . '-' . $path;
		$tmpName = $tmpBase . $tmpPath;
		$data = @file_get_contents($url, false, null, 0, $head['length']);
		if(!$data) {
			return;
		}
		file_put_contents($tmpName, $data);

		$mimeCheck = Storage::mimeType('remcache/' . $tmpPath);

		if(!$mimeCheck || !in_array($mimeCheck, ['image/png', 'image/jpeg'])) {
			$avatar->last_fetched_at = now();
			$avatar->save();
			unlink($tmpName);
			return;
		}

		$disk = Storage::disk($driver);
		$file = $disk->putFileAs($base, new File($tmpName), $path, 'public');
		$permalink = $disk->url($file);

		$avatar->media_path = $base . '/' . $path;
		$avatar->is_remote = true;
		$avatar->cdn_url = $local ? config('app.url') . $permalink : $permalink;
		$avatar->size = $head['length'];
		$avatar->change_count = $avatar->change_count + 1;
		$avatar->last_fetched_at = now();
		$avatar->save();

		Cache::forget('avatar:' . $avatar->profile_id);
		AccountService::del($avatar->profile_id);
		AvatarStorageCleanup::dispatch($avatar)->onQueue($queue)->delay(now()->addMinutes(random_int(3, 15)));

		unlink($tmpName);
	}

	public static function delete(Media $media, $confirm = false)
	{
		if(!$confirm) {
			return;
		}
		MediaDeletePipeline::dispatch($media)->onQueue('mmo');
	}
}
