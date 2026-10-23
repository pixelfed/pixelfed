<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int $response_requested
 * @property string $message
 * @property string $response
 * @property string|null $read_at
 * @property Carbon|null $responded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereResponseRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Contact extends Model
{
    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adminUrl()
    {
        return url('/i/admin/messages/show/'.$this->id);
    }

    public function userResponseUrl()
    {
        return url('/i/contact-admin-response/'.$this->id);
    }

    public function getMessageId(): string
    {
        return $this->id.'-'.(string) Str::uuid().'@'.strtolower(config('pixelfed.domain.app', 'example.org'));
    }
}
