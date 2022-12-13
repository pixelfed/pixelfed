<?php

namespace App\Jobs\StoryPipeline;

use Cache, Log;
use App\Story;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Util\ActivityPub\Helpers;
use App\Services\FollowerService;
use App\Util\Lexer\Bearcap;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use App\Util\ActivityPub\Validator\StoryValidator;
use App\Services\StoryService;
use App\Services\MediaPathService;
use Illuminate\Support\Str;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class StoryFetch implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $activity;

	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($activity)
	{
		$this->activity = $activity;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */
	public function handle()
	{
		$activity = $this->activity;
		$activityId = $activity['id'];
		$activityActor = $activity['actor'];

		if(parse_url($activityId, PHP_URL_HOST) !== parse_url($activityActor, PHP_URL_HOST)) {
			return;
		}

		$bearcap = Bearcap::decode($activity['object']['object']);

		if(!$bearcap) {
			return;
		}

		$url = $bearcap['url'];
		$token = $bearcap['token'];

		if(parse_url($activityId, PHP_URL_HOST) !== parse_url($url, PHP_URL_HOST)) {
			return;
		}

		$version = config('pixelfed.version');
		$appUrl = config('app.url');
		$headers = [
			'Accept'     	=> 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
			'Authorization' => 'Bearer ' . $token,
			'User-Agent' 	=> "(Pixelfed/{$version}; +{$appUrl})",
		];

		try {
			$res = Http::withHeaders($headers)
				->timeout(30)
				->get($url);
		} catch (RequestException $e) {
			return false;
		} catch (ConnectionException $e) {
			return false;
		} catch (\Exception $e) {
			return false;
		}

		$payload = $res->json();

		if(StoryValidator::validate($payload) == false) {
			return;
		}

		if(Helpers::validateUrl($payload['attachment']['url']) == false) {
			return;
		}

		$type = $payload['attachment']['type'] == 'Image' ? 'photo' : 'video';

		$profile = Helpers::profileFetch($payload['attributedTo']);

		$ext = pathinfo($payload['attachment']['url'], PATHINFO_EXTENSION);
		$storagePath = MediaPathService::story($profile);
		$fileName = Str::random(random_int(2, 12)) . '_' . Str::random(random_int(32, 35)) . '_' . Str::random(random_int(1, 14)) . '.' . $ext;
		$contextOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peername' => false
			]
		];
		$ctx = stream_context_create($contextOptions);
		$data = file_get_contents($payload['attachment']['url'], false, $ctx);
		$tmpBase = storage_path('app/remcache/');
		$tmpPath = $profile->id . '-' . $fileName;
		$tmpName = $tmpBase . $tmpPath;
		file_put_contents($tmpName, $data);
		$disk = Storage::disk(config('filesystems.default'));
		$path = $disk->putFileAs($storagePath, new File($tmpName), $fileName, 'public');
		$size = filesize($tmpName);
		unlink($tmpName);

		$story = new Story;
		$story->profile_id = $profile->id;
		$story->object_id = $payload['id'];
		$story->size = $size;
		$story->mime = $payload['attachment']['mediaType'];
		$story->duration = $payload['duration'];
		$story->media_url = $payload['attachment']['url'];
		$story->type = $type;
		$story->public = false;
		$story->local = false;
		$story->active = true;
		$story->path = $path;
		$story->view_count = 0;
		$story->can_reply = $payload['can_reply'];
		$story->can_react = $payload['can_react'];
		$story->created_at = now()->parse($payload['published']);
		$story->expires_at = now()->parse($payload['expiresAt']);
		$story->save();

		StoryService::delLatest($story->profile_id);
	}
}
