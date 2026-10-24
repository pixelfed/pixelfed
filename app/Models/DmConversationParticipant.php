<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $profile_id
 * @property string $state
 * @property int|null $last_read_message_id
 * @property int $unread_count
 * @property Carbon|null $last_activity_at
 * @property Carbon|null $muted_at
 * @property Carbon|null $hidden_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DmConversationParticipant extends Model
{
    public const STATE_ACTIVE = 'active';

    public const STATE_REQUEST = 'request';

    public const STATE_LEFT = 'left';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unread_count' => 'integer',
            'last_activity_at' => 'datetime',
            'muted_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DmConversation::class, 'conversation_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function isActive(): bool
    {
        return $this->state === self::STATE_ACTIVE;
    }

    public function isRequest(): bool
    {
        return $this->state === self::STATE_REQUEST;
    }

    public function hasLeft(): bool
    {
        return $this->state === self::STATE_LEFT;
    }
}
