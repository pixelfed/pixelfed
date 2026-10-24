<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $role
 * @property int $crawlable
 * @property int $show_guests
 * @property int $show_discover
 * @property int $public_dm
 * @property int $hide_cw_search
 * @property int $hide_blocked_search
 * @property int $always_show_cw
 * @property int $compose_media_descriptions
 * @property int $reduce_motion
 * @property int $optimize_screen_reader
 * @property int $high_contrast_mode
 * @property int $video_autoplay
 * @property int $send_email_new_follower
 * @property int $send_email_new_follower_request
 * @property int $send_email_on_share
 * @property int $send_email_on_like
 * @property int $send_email_on_mention
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $show_profile_followers
 * @property int $show_profile_follower_count
 * @property int $show_profile_following
 * @property int $show_profile_following_count
 * @property array<array-key, mixed>|null $compose_settings
 * @property array<array-key, mixed>|null $other
 * @property int $show_atom
 * @property string $can_feature
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereAlwaysShowCw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereCanFeature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereComposeMediaDescriptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereComposeSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereCrawlable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereHideBlockedSearch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereHideCwSearch($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereHighContrastMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereOptimizeScreenReader($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereOther($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting wherePublicDm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereReduceMotion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSendEmailNewFollower($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSendEmailNewFollowerRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSendEmailOnLike($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSendEmailOnMention($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereSendEmailOnShare($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereShowAtom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereShowDiscover($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereShowGuests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereShowProfileFollowerCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereShowProfileFollowers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereShowProfileFollowing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereShowProfileFollowingCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserSetting whereVideoAutoplay($value)
 *
 * @mixin \Eloquent
 */
class UserSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'compose_settings' => 'json',
            'other' => 'json',
        ];
    }
}
