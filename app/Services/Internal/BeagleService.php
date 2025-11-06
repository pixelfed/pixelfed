<?php

namespace App\Services\Internal;

use App\Services\InstanceService;
use App\Services\StatusService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BeagleService
{
    const DEFAULT_RULES_CACHE_KEY = 'pf:services:beagle:default_rules:v1';

    const DISCOVER_CACHE_KEY = 'pf:services:beagle:discover:v1';

    const DISCOVER_POSTS_CACHE_KEY = 'pf:services:beagle:discover-posts:v1';

    public static function getDefaultRules()
    {
        return Cache::remember(self::DEFAULT_RULES_CACHE_KEY, now()->addDays(7), function () {
            try {
                $res = Http::withOptions(['allow_redirects' => false])
                    ->timeout(5)
                    ->connectTimeout(5)
                    ->retry(2, 500)
                    ->get('https://beagle.pixelfed.net/api/v1/common/suggestions/rules');
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

            $json = $res->json();

            if (! isset($json['rule_suggestions']) || ! count($json['rule_suggestions'])) {
                return [];
            }

            return $json['rule_suggestions'];
        });
    }

    public static function getDiscover()
    {
        if ((bool) config_cache('federation.activitypub.enabled') == false) {
            return [];
        }

        if ((bool) config('instance.discover.beagle_api') == false) {
            return [];
        }

        return Cache::remember(self::DISCOVER_CACHE_KEY, now()->addHours(6), function () {
            try {
                $res = Http::withOptions(['allow_redirects' => false])
                    ->withHeaders([
                        'X-Pixelfed-Api' => 1,
                    ])->timeout(5)
                    ->connectTimeout(5)
                    ->retry(2, 500)
                    ->get('https://beagle.pixelfed.net/api/v1/discover');
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

            $json = $res->json();

            if (! isset($json['statuses']) || ! count($json['statuses'])) {
                return [];
            }

            return $json['statuses'];
        });
    }

    public static function getDiscoverPosts()
    {
        if ((bool) config_cache('federation.activitypub.enabled') == false) {
            return [];
        }

        if ((bool) config('instance.discover.beagle_api') == false) {
            return [];
        }

        return Cache::remember(self::DISCOVER_POSTS_CACHE_KEY, now()->addHours(1), function () {
            $posts = collect(self::getDiscover())
                ->filter(function ($post) {
                    $bannedInstances = InstanceService::getBannedDomains();
                    $domain = parse_url($post['id'], PHP_URL_HOST);

                    return ! in_array($domain, $bannedInstances);
                })
                ->map(function ($post) {
                    $domain = parse_url($post['id'], PHP_URL_HOST);
                    if ($domain === config_cache('pixelfed.domain.app')) {
                        $parts = explode('/', $post['id']);
                        $id = array_last($parts);

                        return StatusService::get($id);
                    }

                    $post = Helpers::statusFetch($post['id']);
                    if (! $post) {
                        return;
                    }
                    $id = $post->id;

                    return StatusService::get($id);
                })
                ->filter()
                ->values()
                ->toArray();

            return $posts;
        });
    }
}
