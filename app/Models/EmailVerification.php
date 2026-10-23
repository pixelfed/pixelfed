<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $email
 * @property string $user_token
 * @property string $random_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereRandomToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmailVerification whereUserToken($value)
 *
 * @mixin \Eloquent
 */
class EmailVerification extends Model
{
    public function url()
    {
        $base = config('app.url');
        $path = '/i/confirm-email/'.$this->user_token.'/'.$this->random_token;

        return "{$base}{$path}";
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
