<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomEmoji extends Model
{
    use HasFactory;

    const SCAN_RE = "/(?<=[^[:alnum:]:]|\n|^):([a-zA-Z0-9_]{2,}):(?=[^[:alnum:]:]|$)/x";

    const CACHE_KEY = 'pf:custom_emoji:';

    protected $guarded = [];

    /**
     * Public URL for this emoji's media.
     *
     * When cloud storage is enabled the object is served from the cloud disk
     * (emoji are stored at the same relative media_path on both disks), and
     * falls back to the local /storage URL otherwise.
     */
    public static function urlForPath(?string $mediaPath): ?string
    {
        if (! $mediaPath) {
            return null;
        }

        if ((bool) config_cache('pixelfed.cloud_storage')) {
            return Storage::disk(config('filesystems.cloud'))->url($mediaPath);
        }

        return url('/storage/'.$mediaPath);
    }

    public function url(): ?string
    {
        return self::urlForPath($this->media_path);
    }

    /**
     * The disk emoji media is stored on, and the storage path prefix for it.
     *
     * On cloud storage, objects live at the bare media_path (emoji/{id}.ext).
     * On local storage they live under the public/ disk prefix so they are
     * served through the /storage symlink.
     *
     * @return array{disk: Filesystem, prefix: string}
     */
    public static function storageTarget(): array
    {
        if ((bool) config_cache('pixelfed.cloud_storage')) {
            return [
                'disk' => Storage::disk(config('filesystems.cloud')),
                'prefix' => '',
            ];
        }

        return [
            'disk' => Storage::disk('local'),
            'prefix' => 'public/',
        ];
    }

    /**
     * Store emoji bytes for the given media_path on the active disk.
     */
    public static function storeMedia(string $mediaPath, string $contents): void
    {
        $target = self::storageTarget();
        $target['disk']->put($target['prefix'].$mediaPath, $contents, 'public');
    }

    /**
     * Store an emoji from a local source file for the given media_path on the
     * active disk (used by uploads/imports that already have a file on disk).
     */
    public static function storeMediaFromFile(string $mediaPath, string $sourcePath): void
    {
        $target = self::storageTarget();
        $target['disk']->put(
            $target['prefix'].$mediaPath,
            file_get_contents($sourcePath),
            'public'
        );
    }

    /**
     * Delete emoji media for the given media_path from the active disk.
     */
    public static function deleteMedia(?string $mediaPath): void
    {
        if (! $mediaPath) {
            return;
        }

        $target = self::storageTarget();
        if ($target['disk']->exists($target['prefix'].$mediaPath)) {
            $target['disk']->delete($target['prefix'].$mediaPath);
        }
    }

    /**
     * Whether the emoji media for the given media_path exists on the active disk.
     */
    public static function mediaExists(?string $mediaPath): bool
    {
        if (! $mediaPath) {
            return false;
        }

        $target = self::storageTarget();

        return $target['disk']->exists($target['prefix'].$mediaPath);
    }

    public static function scan($text, $activitypub = false)
    {
        if ((bool) config_cache('federation.custom_emoji.enabled') == false) {
            return [];
        }

        return Str::of($text)
            ->matchAll(self::SCAN_RE)
            ->map(function ($match) use ($activitypub) {
                $tag = Cache::remember(self::CACHE_KEY.$match, 14400, function () use ($match) {
                    $emoji = self::orderBy('id')->whereDisabled(false)->whereShortcode(':'.$match.':')->first();

                    if (! $emoji) {
                        return null;
                    }

                    return [
                        'id' => $emoji->id,
                        'shortcode' => $emoji->shortcode,
                        'media_path' => $emoji->media_path,
                        'updated_at' => optional($emoji->updated_at)->toAtomString(),
                        'disabled' => $emoji->disabled,
                    ];
                });

                if ($tag) {
                    $url = self::urlForPath($tag['media_path']);

                    if ($activitypub == true) {
                        $mediaType = Str::endsWith($url, '.png') ? 'image/png' : 'image/jpg';

                        return [
                            'id' => url('emojis/'.$tag['id']),
                            'type' => 'Emoji',
                            'name' => $tag['shortcode'],
                            'updated' => $tag['updated_at'],
                            'icon' => [
                                'type' => 'Image',
                                'mediaType' => $mediaType,
                                'url' => $url,
                            ],
                        ];
                    } else {
                        return [
                            'shortcode' => $match,
                            'url' => $url,
                            'static_url' => $url,
                            'visible_in_picker' => $tag['disabled'] == false,
                        ];
                    }
                }
            })
            ->filter(function ($tag) use ($activitypub) {
                if ($activitypub == true) {
                    return $tag && isset($tag['icon']);
                } else {
                    return $tag && isset($tag['static_url']);
                }
            })
            ->values()
            ->toArray();
    }
}
