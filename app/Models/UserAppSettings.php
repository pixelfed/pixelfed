<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAppSettings extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'common' => 'json',
        'custom' => 'json',
        'common.timelines.show_public' => 'boolean',
        'common.timelines.show_network' => 'boolean',
        'common.timelines.hide_likes_shares' => 'boolean',
        'common.media.hide_public_behind_cw' => 'boolean',
        'common.media.always_show_cw' => 'boolean',
        'common.media.show_alt_text' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
