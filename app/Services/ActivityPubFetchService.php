<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class ActivityPubFetchService
{
	public static function get($url)
	{
		if(!Helpers::validateUrl($url)) {
			return 0;
		}

		$headers = HttpSignature::instanceActorSign($url, false);
		$headers['Accept'] = 'application/activity+json';

		try {
			$res = Http::withHeaders($headers)
				->timeout(30)
				->connectTimeout(5)
				->retry(3, 500)
				->get($url);
		} catch (RequestException $e) {
			return;
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
