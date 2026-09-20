<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $shortcode
 * @property string|null $media_path
 * @property string|null $domain
 * @property int $disabled
 * @property string|null $uri
 * @property string|null $image_remote_url
 * @property int|null $category_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji duplicateShortcodes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereImageRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereMediaPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereShortcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CustomEmoji whereUri($value)
 *
 * @mixin \Eloquent
 */
class CustomEmoji extends Model
{
    use HasFactory;

    const SCAN_RE = "/(?<=[^[:alnum:]:]|\n|^):([a-zA-Z0-9_]{2,}):(?=[^[:alnum:]:]|$)/x";

    const CACHE_KEY = 'pf:custom_emoji:';

    protected $guarded = [];

    /**
     * Restrict the query to shortcodes that appear on more than one row.
     */
    public function scopeDuplicateShortcodes($query)
    {
        // Select only the grouped column so the aggregate is valid on
        // Postgres (a bare `select *` with GROUP BY is rejected because
        // non-grouped columns must appear in GROUP BY or an aggregate).
        return $query->select('shortcode')->groupBy('shortcode')->havingRaw('count(*) > 1');
    }

    public static function scan($text, $activitypub = false)
    {
        if ((bool) config_cache('federation.custom_emoji.enabled') === false) {
            return [];
        }

        return Str::matchAll(self::SCAN_RE, $text)
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
                        'updated_at' => $emoji->updated_at?->toAtomString(),
                        'disabled' => $emoji->disabled,
                    ];
                });

                if ($tag) {
                    $url = url('/storage/'.$tag['media_path']);

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
                    }

                    return [
                        'shortcode' => $match,
                        'url' => $url,
                        'static_url' => $url,
                        'visible_in_picker' => $tag['disabled'] == false,
                    ];
                }
            })
            ->filter(function ($tag) use ($activitypub) {
                if ($activitypub == true) {
                    return $tag && isset($tag['icon']);
                }

                return $tag && isset($tag['static_url']);
            })
            ->values()
            ->toArray();
    }
}
