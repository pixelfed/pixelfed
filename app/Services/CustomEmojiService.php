<?php

namespace App\Services;

use App\Models\CustomEmoji;
use App\Util\ActivityPub\Helpers;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CustomEmojiService
{
    /**
     * Allowed image mime types for imported custom emoji.
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public static function get($shortcode)
    {
        if ((bool) config_cache('federation.custom_emoji.enabled') == false) {
            return;
        }

        return CustomEmoji::whereShortcode($shortcode)->first();
    }

    public static function import($url, $id = false)
    {
        if ((bool) config_cache('federation.custom_emoji.enabled') == false) {
            return;
        }

        $url = Helpers::validateUrl($url);
        if ($url == false) {
            return;
        }

        $emoji = CustomEmoji::whereUri($url)->first();
        if ($emoji) {
            return;
        }

        // SSRF-hardened JSON fetch: resolve + pin the host to a validated
        // public IP and refuse redirects so the emoji-document request cannot
        // be steered into internal addresses.
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 443;
        $ips = $host ? Helpers::resolvePublicIps($host) : [];
        if (empty($ips)) {
            return;
        }

        try {
            $res = Http::acceptJson()
                ->withOptions([
                    'allow_redirects' => false,
                    'curl' => [
                        CURLOPT_RESOLVE => [
                            $host.':'.((int) $port).':'.implode(',', array_map(
                                fn ($ip) => str_contains($ip, ':') ? '['.$ip.']' : $ip,
                                $ips
                            )),
                        ],
                        CURLOPT_FRESH_CONNECT => true,
                        CURLOPT_FORBID_REUSE => true,
                        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                    ],
                ])
                ->timeout(15)
                ->connectTimeout(5)
                ->get($url);
        } catch (RequestException $e) {
            return;
        } catch (\Exception $e) {
            return;
        }

        if ($res->successful()) {
            $json = $res->json();

            if (
                ! $json ||
                ! isset($json['id']) ||
                ! isset($json['type']) ||
                $json['type'] !== 'Emoji' ||
                ! isset($json['icon']) ||
                ! isset($json['icon']['mediaType']) ||
                ! isset($json['icon']['url']) ||
                ! isset($json['icon']['type']) ||
                $json['icon']['type'] !== 'Image' ||
                ! in_array($json['icon']['mediaType'], self::ALLOWED_MIME_TYPES, true)
            ) {
                return;
            }

            if (Helpers::validateUrl($json['icon']['url']) == false) {
                return;
            }

            if (! self::headCheck($json['icon']['url'])) {
                return;
            }

            $emoji = CustomEmoji::firstOrCreate([
                'shortcode' => $json['name'],
                'domain' => parse_url($json['id'], PHP_URL_HOST),
            ], [
                'uri' => $json['id'],
                'image_remote_url' => $json['icon']['url'],
            ]);

            if ($emoji->wasRecentlyCreated == false) {
                if (Storage::exists('public/'.$emoji->media_path)) {
                    Storage::delete('public/'.$emoji->media_path);
                }
            }

            $ext = '.'.last(explode('/', $json['icon']['mediaType']));
            $mediaPath = 'emoji/'.$emoji->id.$ext;

            try {
                // SSRF-hardened: validated URL, resolved+pinned public IP,
                // no internal redirects, size-capped.
                $maxSize = (int) config('federation.custom_emoji.max_size');
                $body = SecureMediaFetchService::get($json['icon']['url'], $maxSize > 0 ? $maxSize : null);

                if ($body === false) {
                    return;
                }

                Storage::put('public/'.$mediaPath, $body);

                $emoji->media_path = $mediaPath;
                $emoji->save();
            } catch (\Exception $e) {
                // Download failed
                return;
            }

            $name = str_replace(':', '', $json['name']);
            Cache::forget('pf:custom_emoji');
            Cache::forget('pf:custom_emoji:'.$name);
            if ($id) {
                StatusService::del($id);
            }

            return;
        } else {
            return;
        }
    }

    public static function headCheck($url)
    {
        $maxSize = (int) config('federation.custom_emoji.max_size');
        // SSRF-hardened HEAD: validated URL, resolved+pinned public IP, no
        // internal redirects.
        $head = SecureMediaFetchService::head($url, $maxSize > 0 ? $maxSize : null);

        if (! $head) {
            return false;
        }

        if (! in_array($head['mime'], self::ALLOWED_MIME_TYPES, true)) {
            return false;
        }

        if ($maxSize > 0 && $head['length'] > $maxSize) {
            return false;
        }

        return true;
    }

    /**
     * Re-download a remote custom emoji's media from its origin
     * (image_remote_url) and store it locally under public/{media_path}.
     *
     * Used by admin:resyncemoji to repair emoji whose local file is missing
     * or corrupt. Only remote emoji (those with an origin URL) can be resynced.
     *
     * @return string one of: resynced|skipped|failed
     */
    public static function resync(CustomEmoji $emoji): string
    {
        if ((bool) config_cache('federation.custom_emoji.enabled') == false) {
            return 'skipped';
        }

        // Only remote emoji have an origin URL to re-fetch from.
        if (empty($emoji->image_remote_url)) {
            return 'skipped';
        }

        $url = Helpers::validateUrl($emoji->image_remote_url);
        if ($url == false) {
            return 'skipped';
        }

        if (! self::headCheck($url)) {
            return 'failed';
        }

        // Preserve the existing media_path when present; otherwise derive one
        // from the emoji id and the origin URL's extension.
        $mediaPath = $emoji->media_path;
        if (! $mediaPath) {
            $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);
            $ext = $ext ? '.'.strtolower($ext) : '.png';
            $mediaPath = 'emoji/'.$emoji->id.$ext;
        }

        try {
            $maxSize = (int) config('federation.custom_emoji.max_size');
            $body = SecureMediaFetchService::get($url, $maxSize > 0 ? $maxSize : null);

            if ($body === false) {
                return 'failed';
            }

            if (Storage::exists('public/'.$mediaPath)) {
                Storage::delete('public/'.$mediaPath);
            }

            Storage::put('public/'.$mediaPath, $body);

            if ($emoji->media_path !== $mediaPath) {
                $emoji->media_path = $mediaPath;
                $emoji->save();
            }
        } catch (\Throwable $e) {
            return 'failed';
        }

        $name = str_replace(':', '', (string) $emoji->shortcode);
        Cache::forget('pf:custom_emoji');
        Cache::forget('pf:custom_emoji:'.$name);

        return 'resynced';
    }

    public static function all()
    {
        return Cache::rememberForever('pf:custom_emoji', function () {
            $pgsql = config('database.default') === 'pgsql';

            return CustomEmoji::when(! $pgsql, function ($q, $pgsql) {
                return $q->groupBy('shortcode');
            })
                ->whereNull('uri')
                ->get()
                ->map(function ($emojo) {
                    $url = url('storage/'.$emojo->media_path);

                    return [
                        'shortcode' => str_replace(':', '', $emojo->shortcode),
                        'url' => $url,
                        'static_url' => $url,
                        'visible_in_picker' => $emojo->disabled == false,
                    ];
                })
                ->when($pgsql, function ($collection) {
                    return $collection->unique('shortcode');
                })
                ->toJson(JSON_UNESCAPED_SLASHES);
        });
    }
}
