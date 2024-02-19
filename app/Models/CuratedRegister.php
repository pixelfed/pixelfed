<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuratedRegister extends Model
{
    use HasFactory;

    protected $casts = [
        'autofollow_account_ids' => 'array',
        'admin_notes' => 'array',
        'email_verified_at' => 'datetime',
        'admin_notified_at' => 'datetime',
        'action_taken_at' => 'datetime',
    ];

    public function adminStatusLabel()
    {
        if(!$this->email_verified_at) {
            return '<span class="border border-danger px-3 py-1 rounded text-white font-weight-bold">Unverified email</span>';
        }
        if($this->is_accepted) { return 'Approved'; }
        if($this->is_rejected) { return 'Rejected'; }
        if($this->is_awaiting_more_info ) {
            return '<span class="border border-info px-3 py-1 rounded text-white font-weight-bold">Awaiting Details</span>';
        }
        if($this->is_closed ) { return 'Closed'; }

        return '<span class="border border-success px-3 py-1 rounded text-white font-weight-bold">Open</span>';
    }

    public function emailConfirmUrl()
    {
        return url('/auth/sign_up/confirm?sid=' . $this->id . '&code=' . $this->verify_code);
    }

    public function emailReplyUrl()
    {
        return url('/auth/sign_up/concierge?sid=' . $this->id . '&code=' . $this->verify_code . '&sc=' . str_random(8));
    }

    public function adminReviewUrl()
    {
        return url('/i/admin/curated-onboarding/show/' . $this->id);
    }
}
