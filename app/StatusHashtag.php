<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StatusHashtag extends Model
{
    public $fillable = ['status_id', 'hashtag_id'];
}
