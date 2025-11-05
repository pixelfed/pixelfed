<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuratedRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_has_responded'
    ];

    protected $casts = [
        'autofollow_account_ids' => 'array',
        'admin_notes' => 'array',
        'email_verified_at' => 'datetime',
        'admin_notified_at' => 'datetime',
        'action_taken_at' => 'datetime',
        'user_has_responded' => 'boolean',
        'is_awaiting_more_info' => 'boolean',
        'is_accepted' => 'boolean',
        'is_rejected' => 'boolean',
        'is_closed' => 'boolean',
    ];

    public function adminReviewUrl()
    {
        return url('/i/admin/curated-onboarding/show/' . $this->id);
    }
}
