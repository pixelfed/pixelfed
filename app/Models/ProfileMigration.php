<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $acct
 * @property int $followers_count
 * @property int|null $target_profile_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $profile
 * @property-read Profile|null $target
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration whereAcct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration whereFollowersCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration whereTargetProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileMigration whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ProfileMigration extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function target()
    {
        return $this->belongsTo(Profile::class, 'target_profile_id');
    }
}
