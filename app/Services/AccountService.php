<?php

namespace App\Services;

use Cache;
use App\Profile;
use App\Status;
use App\User;
use App\UserSetting;
use App\Models\UserDomainBlock;
use App\Transformer\Api\AccountTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use \NumberFormatter;

class AccountService
{
    const CACHE_KEY = 'pf:services:account:';

    public static function get($id, $softFail = false)
    {
        $res = Cache::remember(self::CACHE_KEY . $id, 43200, function() use($id) {
            $fractal = new Fractal\Manager();
            $fractal->setSerializer(new ArraySerializer());
            $profile = Profile::find($id);
            if(!$profile || $profile->status === 'delete') {
                return null;
            }
            $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
            return $fractal->createData($resource)->toArray();
        });

        if(!$res) {
            return $softFail ? null : abort(404);
        }
        return $res;
    }

    public static function getMastodon($id, $softFail = false)
    {
        $account = self::get($id, $softFail);
        if(!$account) {
            return null;
        }

        if(config('exp.emc') == false) {
            return $account;
        }

        unset(
            $account['header_bg'],
            $account['is_admin'],
            $account['last_fetched_at'],
            $account['local'],
            $account['location'],
            $account['note_text'],
            $account['pronouns'],
            $account['website']
        );

        $account['avatar_static'] = $account['avatar'];
        $account['bot'] = false;
        $account['emojis'] = [];
        $account['fields'] = [];
        $account['header'] = url('/storage/headers/missing.png');
        $account['header_static'] = url('/storage/headers/missing.png');
        $account['last_status_at'] = null;

        return $account;
    }

    public static function del($id)
    {
        Cache::forget('pf:activitypub:user-object:by-id:' . $id);
        return Cache::forget(self::CACHE_KEY . $id);
    }

    public static function settings($id)
    {
        return Cache::remember('profile:compose:settings:' . $id, 604800, function() use($id) {
            $settings = UserSetting::whereUserId($id)->first();
            if(!$settings) {
                return self::defaultSettings();
            }
            return collect($settings)
            ->filter(function($item, $key) {
                return in_array($key, array_keys(self::defaultSettings())) == true;
            })
            ->map(function($item, $key) {
                if($key == 'compose_settings') {
                    $cs = self::defaultSettings()['compose_settings'];
                    $ms = is_array($item) ? $item : [];
                    return array_merge($cs, $ms);
                }

                if($key == 'other') {
                    $other =  self::defaultSettings()['other'];
                    $mo = is_array($item) ? $item : [];
                    return array_merge($other, $mo);
                }
                return $item;
            });
        });
    }

    public static function canEmbed($id)
    {
        return self::settings($id)['other']['disable_embeds'] == false;
    }

    public static function defaultSettings()
    {
        return [
            'crawlable' => true,
            'public_dm' => false,
            'reduce_motion' => false,
            'high_contrast_mode' => false,
            'video_autoplay' => false,
            'show_profile_follower_count' => true,
            'show_profile_following_count' => true,
            'compose_settings' => [
                'default_scope' => 'public',
                'default_license' => 1,
                'media_descriptions' => false
            ],
            'other' => [
                'advanced_atom' => false,
                'disable_embeds' => false,
                'mutual_mention_notifications' => false,
                'hide_collections' => false,
                'hide_like_counts' => false,
                'hide_groups' => false,
                'hide_stories' => false,
                'disable_cw' => false,
            ]
        ];
    }

    public static function syncPostCount($id)
    {
        $profile = Profile::find($id);

        if(!$profile) {
            return false;
        }

        $key = self::CACHE_KEY . 'pcs:' . $id;

        if(Cache::has($key)) {
            return;
        }

        $count = Status::whereProfileId($id)
            ->whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereIn('scope', ['public', 'unlisted', 'private'])
            ->count();

        $profile->status_count = $count;
        $profile->save();

        Cache::put($key, 1, 900);
        return true;
    }

    public static function usernameToId($username)
    {
        $key = self::CACHE_KEY . 'u2id:' . hash('sha256', $username);
        return Cache::remember($key, 14400, function() use($username) {
            $s = Str::of($username);
            if($s->contains('@') && !$s->startsWith('@')) {
                $username = "@{$username}";
            }
            $profile = DB::table('profiles')
                ->whereUsername($username)
                ->first();
            if(!$profile) {
                return null;
            }
            return (string) $profile->id;
        });
    }

    public static function hiddenFollowers($id)
    {
        $account = self::get($id, true);
        if(!$account || !isset($account['local']) || $account['local'] == false) {
            return false;
        }

        return Cache::remember('pf:acct:settings:hidden-followers:' . $id, 43200, function() use($id) {
            $user = User::whereProfileId($id)->first();
            if(!$user) {
                return false;
            }
            $settings = UserSetting::whereUserId($user->id)->first();
            if($settings) {
                return $settings->show_profile_follower_count == false;
            }
            return false;
        });
    }

    public static function hiddenFollowing($id)
    {
        $account = self::get($id, true);
        if(!$account || !isset($account['local']) || $account['local'] == false) {
            return false;
        }

        return Cache::remember('pf:acct:settings:hidden-following:' . $id, 43200, function() use($id) {
            $user = User::whereProfileId($id)->first();
            if(!$user) {
                return false;
            }
            $settings = UserSetting::whereUserId($user->id)->first();
            if($settings) {
                return $settings->show_profile_following_count == false;
            }
            return false;
        });
    }

    public static function setLastActive($id = false)
    {
        if(!$id) { return; }
        $key = 'user:last_active_at:id:' . $id;
        if(!Cache::has($key)) {
            $user = User::find($id);
            if(!$user) { return; }
            $user->last_active_at = now();
            $user->save();
            Cache::put($key, 1, 14400);
        }
        return;
    }

    public static function blocksDomain($pid, $domain = false)
    {
        if(!$domain) {
            return;
        }

        return UserDomainBlock::whereProfileId($pid)->whereDomain($domain)->exists();
    }

    public static function formatNumber($num) {
        if(!$num || $num < 1) {
            return "0";
        }
        $num = intval($num);
        $formatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 1);

        if ($num >= 1000000000) {
            return $formatter->format($num / 1000000000) . 'B';
        } else if ($num >= 1000000) {
            return $formatter->format($num / 1000000) . 'M';
        } elseif ($num >= 1000) {
            return $formatter->format($num / 1000) . 'K';
        } else {
            return $formatter->format($num);
        }
    }

    public static function getMetaDescription($id)
    {
        $account = self::get($id, true);

        if(!$account) return "";

        $posts = self::formatNumber($account['statuses_count']) . ' Posts, ';
        $following = self::formatNumber($account['following_count']) . ' Following, ';
        $followers = self::formatNumber($account['followers_count']) . ' Followers';
        $note = $account['note'] && strlen($account['note']) ?
            ' · ' . \Purify::clean(strip_tags(str_replace("\n", '', str_replace("\r", '', $account['note'])))) :
            '';
        return $posts . $following . $followers . $note;
    }
}
