<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FEP-044f approval stamp issued by a local author for a remote quote post.
 *
 * @property int $id
 * @property int $profile_id
 * @property int $status_id
 * @property int $actor_id
 * @property string $quote_url
 * @property string|null $request_url
 * @property string $state
 */
class QuoteAuthorization extends Model
{
    use HasSnowflakePrimary;

    public const STATE_APPROVED = 'approved';

    public const STATE_REVOKED = 'revoked';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * The local profile whose post was quoted.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    /**
     * The local post that was quoted.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    /**
     * The remote profile that authored the quote post.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'actor_id');
    }

    public function isApproved(): bool
    {
        return $this->state === self::STATE_APPROVED;
    }

    public function isRevoked(): bool
    {
        return $this->state === self::STATE_REVOKED;
    }

    /**
     * Public URL of the stamp. Must share a host with the quoted author's
     * actor id, which remote servers check during verification.
     */
    public function permalink(): string
    {
        return $this->profile->permalink('/quote_authorizations/'.$this->id);
    }

    public function scopeApproved($query)
    {
        return $query->where('state', self::STATE_APPROVED);
    }

    public function scopeRevoked($query)
    {
        return $query->where('state', self::STATE_REVOKED);
    }
}
