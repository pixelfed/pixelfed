<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $item_id
 * @property string|null $item_type
 * @property string|null $action
 * @property string|null $message
 * @property string|null $link
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountLog whereUserId($value)
 *
 * @mixin \Eloquent
 */
class AccountLog extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
