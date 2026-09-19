<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $oidc_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOidcMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOidcMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOidcMapping query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOidcMapping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOidcMapping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOidcMapping whereOidcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOidcMapping whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserOidcMapping whereUserId($value)
 * @mixin \Eloquent
 */
class UserOidcMapping extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
