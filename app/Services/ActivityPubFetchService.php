<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Http\Client\ConnectionException;

class ActivityPubFetchService
{
	public static function get($url)
	{
		if(!Helpers::validateUrl($url)) {
			return 0;
		}

		$headers = HttpSignature::instanceActorSign($url, false);
		$headers['Accept'] = 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"';
		$headers['User-Agent'] = '(Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')';

		try {
			$res = Http::withHeaders($headers)
				->timeout(10)
				->get($url);
		} catch (ConnectionException $e) {
			return;
		} catch (Exception $e) {
			return;
		}
		if(!$res->ok()) {
			return;
		}
		return $res->body();
	}
}
