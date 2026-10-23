<?php

namespace App\Models;

use App\Services\UserAgentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $ip
 * @property string $user_agent
 * @property string|null $fingerprint
 * @property string|null $name
 * @property int|null $trusted
 * @property string|null $last_active_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereFingerprint($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereLastActiveAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereTrusted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserDevice whereUserId($value)
 *
 * @mixin \Eloquent
 */
class UserDevice extends Model
{
    protected $guarded = [];

    public $timestamps = [
        'last_active_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUserAgent()
    {
        if (! $this->user_agent) {
            return 'Unknown';
        }

        return new UserAgentService($this->user_agent);
    }
}
