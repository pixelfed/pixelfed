<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuratedRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_has_responded',
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

    public function adminStatusLabel()
    {
        if ($this->user_has_responded) {
            return '<span class="border border-warning px-3 py-1 rounded text-white font-weight-bold">Awaiting Admin Response</span>';
        }
        if (! $this->email_verified_at) {
            return '<span class="border border-danger px-3 py-1 rounded text-white font-weight-bold">Unverified email</span>';
        }
        if ($this->is_approved) {
            return '<span class="badge badge-success bg-success text-dark">Approved</span>';
        }
        if ($this->is_rejected) {
            return '<span class="badge badge-danger bg-danger text-white">Rejected</span>';
        }
        if ($this->is_awaiting_more_info) {
            return '<span class="border border-info px-3 py-1 rounded text-white font-weight-bold">Awaiting User Response</span>';
        }
        if ($this->is_closed) {
            return '<span class="border border-muted px-3 py-1 rounded text-white font-weight-bold" style="opacity:0.5">Closed</span>';
        }

        return '<span class="border border-success px-3 py-1 rounded text-white font-weight-bold">Open</span>';
    }

    public function emailConfirmUrl()
    {
        return url('/auth/sign_up/confirm?sid='.$this->id.'&code='.$this->verify_code);
    }

    public function emailReplyUrl()
    {
        return url('/auth/sign_up/concierge?sid='.$this->id.'&code='.$this->verify_code.'&sc='.str_random(8));
    }

    public function adminReviewUrl()
    {
        return url('/i/admin/curated-onboarding/show/'.$this->id);
    }
}
