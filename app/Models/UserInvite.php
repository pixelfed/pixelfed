<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $profile_id
 * @property string $email
 * @property string|null $message
 * @property string $key
 * @property string $token
 * @property string|null $valid_until
 * @property string|null $used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $sender
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserInvite whereValidUntil($value)
 *
 * @mixin \Eloquent
 */
class UserInvite extends Model
{
    public function sender()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function url()
    {
        return url("/i/invite/code/{$this->key}/{$this->token}");
    }
}
