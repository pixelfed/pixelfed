<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $profile_id
 * @property int $user_id
 * @property array<array-key, mixed>|null $roles
 * @property array<array-key, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles whereRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserRoles whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserRoles extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'meta' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
