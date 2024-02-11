<?php

namespace App\Models;

use App\Services\AccountService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    use HasFactory;

    public $fillable = [
        'profile_id',
        'active',
        'show_captions',
        'show_license',
        'show_location',
        'show_timestamp',
        'show_link',
        'show_avatar',
        'show_bio',
        'profile_layout',
        'profile_source',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function url($suffix = '')
    {
        $account = AccountService::get($this->profile_id);
        if (! $account) {
            return null;
        }

        return 'https://'.config('portfolio.domain').config('portfolio.path').'/'.$account['username'].$suffix;
    }

    public function permalink($suffix = '')
    {
        $account = AccountService::get($this->profile_id);

        return config('app.url').'/account/portfolio/'.$account['username'].$suffix;
    }
}
