<?php

namespace App\Models;

use App\Services\UserAgentService;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class UserDevice extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUserAgent()
    {
        if (! $this->user_agent) {
            return 'Unknown';
        }

        return new UserAgentService($this->user_agent);
    }
}
