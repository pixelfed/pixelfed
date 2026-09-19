<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $profile_id
 * @property string|null $acct
 * @property string|null $uri
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias whereAcct($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileAlias whereUri($value)
 *
 * @mixin \Eloquent
 */
class ProfileAlias extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
