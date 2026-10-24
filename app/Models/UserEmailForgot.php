<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property Carbon|null $email_sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot whereEmailSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot whereReferrer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserEmailForgot whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserEmailForgot extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
        ];
    }
}
