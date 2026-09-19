<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $profile_id
 * @property string $domain
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDomainBlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDomainBlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDomainBlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDomainBlock whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDomainBlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDomainBlock whereProfileId($value)
 *
 * @mixin \Eloquent
 */
class UserDomainBlock extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }
}
