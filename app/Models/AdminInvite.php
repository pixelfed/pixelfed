<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminInvite extends Model
{
    protected $casts = [
        'used_by' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'description',
        'message',
        'max_uses',
        'uses',
        'skip_email_verification',
        'expires_at',
        'admin_user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (AdminInvite $invite) {
            $invite->invite_code = Str::uuid().Str::random(random_int(1, 6));
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
