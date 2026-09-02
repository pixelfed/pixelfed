<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable('group_id', 'status_id', 'profile_id', 'comment_id')]
class GroupLike extends Model
{
    use HasFactory;
}
