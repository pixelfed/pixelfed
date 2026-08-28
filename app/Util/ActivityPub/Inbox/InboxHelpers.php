<?php

namespace App\Util\ActivityPub\Inbox;

use App\Notification;
use App\Profile;
use App\Services\AccountService;
use App\Services\UserFilterService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;

trait InboxHelpers
{
    /**
     * Validate an actor URL and fetch/create the corresponding Profile.
     * Returns null if the URL is invalid or the profile cannot be resolved.
     */
    public function validateAndFetchActor(string $actorUrl): ?Profile
    {
        if (! Helpers::validateUrl($actorUrl)) {
            return null;
        }

        return Helpers::profileFetch($actorUrl);
    }

    /**
     * Check if a profile has blocked the given domain.
     */
    public function isDomainBlocked(int $profileId, ?string $domain): bool
    {
        if (! $domain) {
            return false;
        }

        return AccountService::blocksDomain($profileId, $domain) === true;
    }

    /**
     * Check if a profile has blocked a specific actor via user filters.
     */
    public function isUserBlocked(int $profileId, int $actorId): bool
    {
        $blocks = UserFilterService::blocks($profileId);

        return $blocks && in_array($actorId, $blocks);
    }

    /**
     * Delete notifications matching the given criteria.
     *
     * @param  array<string, mixed>  $criteria  Column => value pairs for the query.
     */
    public function deleteNotifications(array $criteria): void
    {
        $query = Notification::query();

        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        $query->get()->each(function ($notification) {
            $notification->forceDelete();
        });
    }

    /**
     * Clear cached profile follower/following counts for the given profile IDs.
     */
    public function clearProfileCache(int ...$profileIds): void
    {
        foreach ($profileIds as $id) {
            Cache::forget('profile:follower_count:'.$id);
            Cache::forget('profile:following_count:'.$id);
            Cache::forget('profile:following:'.$id);
            Cache::forget('profile:followers:'.$id);
        }
    }

    /**
     * Strip a trailing '/activity' suffix from a URL.
     */
    public function stripActivitySuffix(string $url): string
    {
        if (str_ends_with($url, '/activity')) {
            return substr($url, 0, -9);
        }

        return $url;
    }

    /**
     * Verify that the host of two URLs match (e.g. actor and object belong to same origin).
     */
    public function hostsMatch(string $url1, string $url2): bool
    {
        return parse_url($url1, PHP_URL_HOST) === parse_url($url2, PHP_URL_HOST);
    }

    /**
     * Ensure payload has all required keys, returning false if any are missing.
     *
     * @param  array<int, string>  $keys
     */
    public function payloadHasKeys(array $keys): bool
    {
        foreach ($keys as $key) {
            if (! isset($this->payload[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Invalidate AccountService cache for the given profile IDs.
     */
    public function clearAccountCache(int ...$profileIds): void
    {
        foreach ($profileIds as $id) {
            AccountService::del($id);
        }
    }
}
