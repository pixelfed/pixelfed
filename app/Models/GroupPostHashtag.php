<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupPostHashtag extends Model
{
    use HasFactory;

    public $fillable = [
        'group_id',
        'group_post_id',
        'status_id',
        'hashtag_id',
        'profile_id',
        'nsfw',
    ];

    public $timestamps = false;
}
