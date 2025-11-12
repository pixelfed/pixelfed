<?php

namespace App\Services;

use App\Util\ActivityPub\HttpSignature;
use Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ActivityPubFetchService
{
    const CACHE_KEY = 'pf:services:apfetchs:';

    public static function get($url, $validateUrl = true)
    {
        if (! self::validateUrl($url)) {
            return false;
        }
        $domain = parse_url($url, PHP_URL_HOST);
        if (! $domain) {
            return false;
        }
        $domainKey = base64_encode($domain);
        $urlKey = hash('sha256', $url);
        $key = self::CACHE_KEY.$domainKey.':'.$urlKey;

        return Cache::remember($key, 450, function () use ($url) {
            return self::fetchRequest($url);
        });
    }

    public static function validateUrl($url)
    {
        if (is_array($url)) {
            $url = $url[0];
        }

        $localhosts = [
            '127.0.0.1', 'localhost', '::1',
        ];

        if (strtolower(mb_substr($url, 0, 8)) !== 'https://') {
            return false;
        }

        if (substr_count($url, '://') !== 1) {
            return false;
        }

        if (mb_substr($url, 0, 8) !== 'https://') {
            $url = 'https://'.substr($url, 8);
        }

        $valid = filter_var($url, FILTER_VALIDATE_URL);

        if (! $valid) {
            return false;
        }

        $host = parse_url($valid, PHP_URL_HOST);

        if (in_array($host, $localhosts)) {
            return false;
        }

        if (config('security.url.verify_dns')) {
            if (DomainService::hasValidDns($host) === false) {
                return false;
            }
        }

        if (app()->environment() === 'production') {
            $bannedInstances = InstanceService::getBannedDomains();
            if (in_array($host, $bannedInstances)) {
                return false;
            }
        }

        return $url;
    }

    public static function fetchRequest($url, $returnJsonFormat = false)
    {
        $baseHeaders = [
            'Accept' => 'application/activity+json',
        ];

        $headers = HttpSignature::instanceActorSign($url, false, $baseHeaders, 'get');
        $headers['Accept'] = 'application/activity+json';
        $headers['User-Agent'] = 'PixelFedBot/1.0.0 (Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')';

        try {
            $res = Http::withOptions([
                'allow_redirects' => [
                    'max' => 2,
                    'protocols' => ['https'],
                ]])
                ->withHeaders($headers)
                ->timeout(30)
                ->connectTimeout(5)
                ->retry(3, 500)
                ->get($url);
        } catch (RequestException $e) {
            return;
        } catch (ConnectionException $e) {
            return;
        } catch (\Exception $e) {
            return;
        }

        if (! $res->ok()) {
            return;
        }

        if (! $res->hasHeader('Content-Type')) {
            return;
        }

        $acceptedTypes = [
            'application/activity+json; charset=utf-8',
            'application/activity+json',
            'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
        ];

        $contentType = $res->getHeader('Content-Type')[0];

        if (! $contentType) {
            return;
        }

        if (! in_array($contentType, $acceptedTypes)) {
            return;
        }

        return $returnJsonFormat ? $res->json() : $res->body();
    }
}
