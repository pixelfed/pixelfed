<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $profile_id
 * @property string|null $pronouns
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun wherePronouns($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPronoun whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserPronoun extends Model
{
    use HasFactory;

    protected $guarded = [];
}
