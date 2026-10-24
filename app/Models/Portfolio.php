<?php

namespace App\Models;

use App\Services\AccountService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $profile_id
 * @property int|null $active
 * @property int|null $show_captions
 * @property int|null $show_license
 * @property int|null $show_location
 * @property int|null $show_timestamp
 * @property int|null $show_link
 * @property string|null $profile_source
 * @property int|null $show_avatar
 * @property int|null $show_bio
 * @property string|null $profile_layout
 * @property string|null $profile_container
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereProfileContainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereProfileLayout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereProfileSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereShowAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereShowBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereShowCaptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereShowLicense($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereShowLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereShowLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereShowTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Portfolio whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Portfolio extends Model
{
    use HasFactory;

    public $fillable = [
        'profile_id',
        'active',
        'show_captions',
        'show_license',
        'show_location',
        'show_timestamp',
        'show_link',
        'show_avatar',
        'show_bio',
        'profile_layout',
        'profile_source',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
        ];
    }

    public function url($suffix = '')
    {
        $account = AccountService::get($this->profile_id);
        if (! $account) {
            return null;
        }

        return 'https://'.config('portfolio.domain').config('portfolio.path').'/'.$account['username'].$suffix;
    }

    public function permalink($suffix = ''): string
    {
        $account = AccountService::get($this->profile_id);

        return config('app.url').'/account/portfolio/'.$account['username'].$suffix;
    }
}
