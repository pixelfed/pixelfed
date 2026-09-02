<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Circle extends Model
{
    public function members()
    {
        return $this->hasManyThrough(
            Profile::class,
            CircleProfile::class,
            'circle_id',
            'id',
            'id',
            'profile_id'
        );
    }

    public function owner()
    {
        return $this->belongsTo(Profile::class, 'profile_id');
    }

    public function url()
    {
        return url("/i/circle/show/{$this->id}");
    }
}
