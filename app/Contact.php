<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Contact extends Model
{
    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adminUrl()
    {
        return url('/i/admin/messages/show/'.$this->id);
    }

    public function userResponseUrl()
    {
        return url('/i/contact-admin-response/'.$this->id);
    }

    public function getMessageId()
    {
        return $this->id.'-'.(string) Str::uuid().'@'.strtolower(config('pixelfed.domain.app', 'example.org'));
    }
}
