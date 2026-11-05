<?php

namespace App\Models;

use App\Services\RegistrationBlockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $type
 * @property string $match_type
 * @property string $pattern
 * @property string|null $note
 * @property bool $is_active
 * @property Carbon|null $expires_at
 * @property int $hits
 * @property Carbon|null $last_hit_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $creator
 *
 * @method static Builder<static>|RegistrationBlock newModelQuery()
 * @method static Builder<static>|RegistrationBlock newQuery()
 * @method static Builder<static>|RegistrationBlock query()
 * @method static Builder<static>|RegistrationBlock enforced()
 *
 * @mixin \Eloquent
 */
class RegistrationBlock extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
            'last_hit_at' => 'datetime',
            'hits' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => RegistrationBlockService::flush());
        static::deleted(fn () => RegistrationBlockService::flush());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Active rules that have not expired.
     *
     * @param  Builder<RegistrationBlock>  $query
     */
    protected function scopeEnforced(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function status(): string
    {
        if (! $this->is_active) {
            return 'disabled';
        }

        return $this->isExpired() ? 'expired' : 'active';
    }

    /**
     * @return array<string, mixed>
     */
    public function toApi(): array
    {
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'match_type' => $this->match_type,
            'pattern' => $this->pattern,
            'note' => $this->note,
            'status' => $this->status(),
            'is_active' => (bool) $this->is_active,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'hits' => (int) $this->hits,
            'last_hit_at' => $this->last_hit_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
