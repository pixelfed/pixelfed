<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @method static Builder|AdminInvite whereInviteCode(string $value)
 */
#[Unguarded]
class AdminInvite extends Model
{
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
