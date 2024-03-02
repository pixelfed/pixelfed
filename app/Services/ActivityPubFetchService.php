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
            $res = Http::withOptions(['allow_redirects' => false])
                ->withHeaders($headers)
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

        if(!$res->hasHeader('Content-Type')) {
            return;
        }

        $acceptedTypes = [
            'application/activity+json; charset=utf-8',
            'application/activity+json',
            'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'
        ];

        $contentType = $res->getHeader('Content-Type')[0];

        if(!$contentType) {
            return;
        }

        if(!in_array($contentType, $acceptedTypes)) {
            return;
        }

        return $res->body();
    }
}
