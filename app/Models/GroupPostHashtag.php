<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[WithoutTimestamps]
#[Fillable('group_id', 'group_post_id', 'status_id', 'hashtag_id', 'profile_id', 'nsfw')]
class GroupPostHashtag extends Model
{
    use HasFactory;
}
