<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Hashtag extends Model
{
    public $fillable = ['name', 'slug'];

    public function posts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Status::class,
            StatusHashtag::class,
            'hashtag_id',
            'id',
            'id',
            'status_id'
        );
    }

    public function url($suffix = '')
    {
        return config('routes.hashtag.base').$this->slug.$suffix;
    }
}
