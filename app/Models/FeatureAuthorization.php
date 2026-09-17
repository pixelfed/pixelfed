<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureAuthorization extends Model
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
     * The local profile that was featured.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    /**
     * The remote profile that owns the collection.
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
     * Public URL of the stamp. Must share a host with the featured
     * actor's id, which remote servers check during verification.
     */
    public function permalink(): string
    {
        return $this->profile->permalink('/stamps/'.$this->id);
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
