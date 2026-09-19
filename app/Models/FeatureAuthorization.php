<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int $actor_id
 * @property string $collection_url
 * @property string|null $collection_name
 * @property string|null $request_url
 * @property string $state
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Profile|null $actor
 * @property-read Profile|null $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization revoked()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereCollectionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereCollectionUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereRequestUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereRevokedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FeatureAuthorization whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
