<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuratedRegisterActivity extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'admin_notified_at' => 'datetime',
        'action_taken_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(CuratedRegister::class, 'register_id');
    }

    public function emailReplyUrl()
    {
        return url('/auth/sign_up/concierge?sid='.$this->register_id.'&id='.$this->id.'&code='.$this->secret_code);
    }

    public function adminReviewUrl()
    {
        $url = '/i/admin/curated-onboarding/show/'.$this->register_id.'/?ah='.$this->id;
        if ($this->reply_to_id) {
            $url .= '&rtid='.$this->reply_to_id;
        }

        return url($url);
    }
}
