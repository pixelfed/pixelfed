<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $profile_id
 * @property array<array-key, mixed>|null $common
 * @property array<array-key, mixed>|null $custom
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings whereCommon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings whereCustom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserAppSettings whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserAppSettings extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'common' => 'json',
            'custom' => 'json',
            'common.timelines.show_public' => 'boolean',
            'common.timelines.show_network' => 'boolean',
            'common.timelines.hide_likes_shares' => 'boolean',
            'common.media.hide_public_behind_cw' => 'boolean',
            'common.media.always_show_cw' => 'boolean',
            'common.media.show_alt_text' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
