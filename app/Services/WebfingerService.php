<?php

namespace App\Services;

use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use App\Util\Webfinger\WebfingerUrl;

class WebfingerService
{
    public static function rawGet($url)
    {
        if (empty($url)) {
            return false;
        }

        $link = self::fetchSelfLink(WebfingerUrl::get($url));

        return $link ?: false;
    }

    public static function lookup($query, $mastodonMode = false)
    {
        return (new self)->run($query, $mastodonMode);
    }

    protected function run($query, $mastodonMode)
    {
        if ($profile = Profile::whereUsername($query)->first()) {
            return $mastodonMode ?
                AccountService::getMastodon($profile->id, true) :
                AccountService::get($profile->id);
        }

        $link = self::fetchSelfLink(WebfingerUrl::generateWebfingerUrl($query));
        if (! $link) {
            return [];
        }

        $profile = Helpers::profileFetch($link);
        if (! $profile instanceof Profile) {
            return [];
        }

        return $mastodonMode ?
            AccountService::getMastodon($profile->id, true) :
            AccountService::get($profile->id);
    }

    protected static function fetchSelfLink($url)
    {
        if (! $url || ! is_string($url) || ! str_starts_with($url, 'https://')) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! $host) {
            return null;
        }

        if (in_array($host, InstanceService::getBannedDomains())) {
            return null;
        }

        $webfinger = FetchCacheService::getJson($url);
        if (! $webfinger || ! isset($webfinger['links']) || ! is_array($webfinger['links']) || empty($webfinger['links'])) {
            return null;
        }

        return collect($webfinger['links'])
            ->filter(function ($link) {
                return $link &&
                    isset($link['rel'], $link['type'], $link['href']) &&
                    $link['rel'] === 'self' &&
                    in_array($link['type'], ['application/activity+json', 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"']);
            })
            ->pluck('href')
            ->first();
    }
}
