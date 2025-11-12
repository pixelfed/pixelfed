<?php

namespace App\Services;

use App\Instance;
use App\Util\Blurhash\Blurhash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InstanceService
{
    const CACHE_KEY_BY_DOMAIN = 'pf:services:instance:by_domain:';

    const CACHE_KEY_BANNED_DOMAINS = 'instances:banned:domains';

    const CACHE_KEY_UNLISTED_DOMAINS = 'instances:unlisted:domains';

    const CACHE_KEY_NSFW_DOMAINS = 'instances:auto_cw:domains';

    const CACHE_KEY_STATS = 'pf:services:instances:stats';

    const CACHE_KEY_TOTAL_POSTS = 'pf:services:instances:self:total-posts';

    const CACHE_KEY_BANNER_BLURHASH = 'pf:services:instance:header-blurhash:v1';

    const CACHE_KEY_API_PEERS_LIST = 'pf:services:instance:api:peers:list:v0';

    public function __construct()
    {
        ini_set('memory_limit', config('pixelfed.memory_limit', '1024M'));
    }

    public static function getByDomain($domain)
    {
        return Cache::remember(self::CACHE_KEY_BY_DOMAIN.$domain, 3600, function () use ($domain) {
            return Instance::whereDomain($domain)->first();
        });
    }

    public static function getBannedDomains()
    {
        return Cache::remember(self::CACHE_KEY_BANNED_DOMAINS, 1209600, function () {
            return Instance::whereBanned(true)->pluck('domain')->toArray();
        });
    }

    public static function getUnlistedDomains()
    {
        return Cache::remember(self::CACHE_KEY_UNLISTED_DOMAINS, 1209600, function () {
            return Instance::whereUnlisted(true)->pluck('domain')->toArray();
        });
    }

    public static function getNsfwDomains()
    {
        return Cache::remember(self::CACHE_KEY_NSFW_DOMAINS, 1209600, function () {
            return Instance::whereAutoCw(true)->pluck('domain')->toArray();
        });
    }

    public static function software($domain)
    {
        $key = 'instances:software:'.strtolower($domain);

        return Cache::remember($key, 86400, function () use ($domain) {
            $instance = Instance::whereDomain($domain)->first();
            if (! $instance) {
                return;
            }

            return $instance->software;
        });
    }

    public static function stats()
    {
        return Cache::remember(self::CACHE_KEY_STATS, 86400, function () {
            return [
                'total_count' => Instance::count(),
                'new_count' => Instance::where('created_at', '>', now()->subDays(14))->count(),
                'banned_count' => Instance::whereBanned(true)->count(),
                'nsfw_count' => Instance::whereAutoCw(true)->count(),
            ];
        });
    }

    public static function refresh()
    {
        Cache::forget(self::CACHE_KEY_BANNED_DOMAINS);
        Cache::forget(self::CACHE_KEY_UNLISTED_DOMAINS);
        Cache::forget(self::CACHE_KEY_NSFW_DOMAINS);
        Cache::forget(self::CACHE_KEY_STATS);
        Cache::forget(self::CACHE_KEY_API_PEERS_LIST);

        self::getBannedDomains();
        self::getUnlistedDomains();
        self::getNsfwDomains();

        return true;
    }

    public static function totalLocalStatuses()
    {
        if (config('instance.enable_cc')) {
            return config_cache('instance.stats.total_local_posts');
        }

        return Cache::remember(self::CACHE_KEY_TOTAL_POSTS, now()->addHour(), function () {
            return DB::table('statuses')
                ->whereNull('deleted_at')
                ->where('local', true)
                ->whereNot('type', 'share')
                ->count();
        });
    }

    public static function headerBlurhash()
    {
        return Cache::rememberForever(self::CACHE_KEY_BANNER_BLURHASH, function () {
            if (str_ends_with(config_cache('app.banner_image'), 'headers/default.jpg')) {
                return 'UzJR]l{wHZRjM}R%XRkCH?X9xaWEjZj]kAjt';
            }
            $cached = config_cache('instance.banner.blurhash');

            if ($cached) {
                return $cached;
            }

            $file = config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg'));

            $image = imagecreatefromstring(file_get_contents($file));
            if (! $image) {
                return 'UzJR]l{wHZRjM}R%XRkCH?X9xaWEjZj]kAjt';
            }
            $width = imagesx($image);
            $height = imagesy($image);

            $pixels = [];
            for ($y = 0; $y < $height; $y++) {
                $row = [];
                for ($x = 0; $x < $width; $x++) {
                    $index = imagecolorat($image, $x, $y);
                    $colors = imagecolorsforindex($image, $index);

                    $row[] = [$colors['red'], $colors['green'], $colors['blue']];
                }
                $pixels[] = $row;
            }

            // Free the allocated GdImage object from memory:
            imagedestroy($image);

            $components_x = 4;
            $components_y = 4;
            $blurhash = Blurhash::encode($pixels, $components_x, $components_y);
            if (strlen($blurhash) > 191) {
                return 'UzJR]l{wHZRjM}R%XRkCH?X9xaWEjZj]kAjt';
            }

            ConfigCacheService::put('instance.banner.blurhash', $blurhash);

            return $blurhash;
        });
    }
}
