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
	public static function get($url, $validateUrl = true)
	{
        if($validateUrl === true) {
    		if(!Helpers::validateUrl($url)) {
    			return 0;
    		}
        }

		$baseHeaders = [
			'Accept' => 'application/activity+json, application/ld+json',
		];

		$headers = HttpSignature::instanceActorSign($url, false, $baseHeaders, 'get');
		$headers['Accept'] = 'application/activity+json, application/ld+json';
		$headers['User-Agent'] = 'PixelFedBot/1.0.0 (Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')';

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
