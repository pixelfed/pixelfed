<?php

namespace App;

use App\Services\UserAgentService;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $guarded = [];

    public $timestamps = [
        'last_active_at',
    ];

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
