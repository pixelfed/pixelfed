<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $type
 * @property string $participants_hash
 * @property string|null $name
 * @property string|null $context_uri
 * @property string|null $conversation_uri
 * @property int|null $created_by_profile_id
 * @property int|null $last_message_id
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DmConversation extends Model
{
    use HasSnowflakePrimary;

    public const TYPE_DM = 'dm';

    public const TYPE_GROUP = 'group';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * A conversation is its set of participants. The same people always land
     * in the same conversation, whichever server started the thread.
     *
     * @param  array<int, int|string>  $profileIds
     */
    public static function participantsHash(array $profileIds): string
    {
        $ids = array_values(array_unique(array_map('intval', $profileIds)));
        sort($ids);

        return hash('sha256', implode(':', $ids));
    }

    public static function dmHash(int $a, int $b): string
    {
        return self::participantsHash([$a, $b]);
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function participants(): HasMany
    {
        return $this->hasMany(DmConversationParticipant::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DmMessage::class, 'conversation_id');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(DmMessage::class, 'last_message_id');
    }

    /**
     * The ActivityPub `context` replies are sent with.
     */
    public function contextUri(): string
    {
        return $this->context_uri ?: self::localContextUri($this->id);
    }

    /**
     * The OStatus `conversation` replies are sent with. Mastodon groups
     * statuses by this value when it cannot resolve inReplyTo.
     */
    public function conversationUri(): string
    {
        return $this->conversation_uri ?: $this->contextUri();
    }

    public static function localContextUri(int|string $id): string
    {
        return url('/i/dm/contexts/'.$id);
    }
}
