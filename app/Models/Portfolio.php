<?php

namespace App\Models;

use App\Services\AccountService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable('profile_id', 'active', 'show_captions', 'show_license', 'show_location', 'show_timestamp', 'show_link', 'show_avatar', 'show_bio', 'profile_layout', 'profile_source')]
class Portfolio extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'json',
        ];
    }

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
