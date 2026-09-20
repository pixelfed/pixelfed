<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $name
 * @property string $invite_code
 * @property string|null $description
 * @property string|null $message
 * @property int|null $max_uses
 * @property int|null $uses
 * @property int $skip_email_verification
 * @property Carbon|null $expires_at
 * @property array<array-key, mixed>|null $used_by
 * @property int|null $admin_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|AdminInvite newModelQuery()
 * @method static Builder<static>|AdminInvite newQuery()
 * @method static Builder<static>|AdminInvite query()
 * @method static Builder<static>|AdminInvite whereAdminUserId($value)
 * @method static Builder<static>|AdminInvite whereCreatedAt($value)
 * @method static Builder<static>|AdminInvite whereDescription($value)
 * @method static Builder<static>|AdminInvite whereExpiresAt($value)
 * @method static Builder<static>|AdminInvite whereId($value)
 * @method static Builder<static>|AdminInvite whereInviteCode($value)
 * @method static Builder<static>|AdminInvite whereMaxUses($value)
 * @method static Builder<static>|AdminInvite whereMessage($value)
 * @method static Builder<static>|AdminInvite whereName($value)
 * @method static Builder<static>|AdminInvite whereSkipEmailVerification($value)
 * @method static Builder<static>|AdminInvite whereUpdatedAt($value)
 * @method static Builder<static>|AdminInvite whereUsedBy($value)
 * @method static Builder<static>|AdminInvite whereUses($value)
 *
 * @mixin \Eloquent
 */
class AdminInvite extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'used_by' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AdminInvite $invite) {
            $invite->invite_code = (string) Str::uuid().Str::random(random_int(1, 6));
        });
    }

    public function url(): string
    {
        return url('/auth/invite/a/'.$this->invite_code);
    }

    public function isActive(): bool
    {
        return $this->hasUsesRemaining() && ! $this->hasExpired();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function hasUsesRemaining(): bool
    {
        return $this->max_uses === 0 || is_null($this->max_uses) || $this->uses < $this->max_uses;
    }
}
