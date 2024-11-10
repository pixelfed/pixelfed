<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLike extends Model
{
    use HasFactory;

    public $fillable = ['group_id', 'status_id', 'profile_id', 'comment_id'];
}
