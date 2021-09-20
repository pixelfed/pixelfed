<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;

class ActivityPubFetchService
{
	public static function get($url)
	{
		if(!Helpers::validateUrl($url)) {
			return 0;
		}

		$headers = HttpSignature::instanceActorSign($url, false);
		$headers['Accept'] = 'application/activity+json, application/json';
		$headers['User-Agent'] = '(Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')';

		return Http::withHeaders($headers)
			->timeout(30)
			->get($url)
			->body();
	}
}
