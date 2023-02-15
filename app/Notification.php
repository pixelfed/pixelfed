<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    protected $guarded = [];

    public function actor()
    {
        return $this->belongsTo(Profile::class, 'actor_id', 'id');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function item()
    {
        return $this->morphTo();
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'item_id', 'id');
    }

    public function tag()
    {
        return $this->hasOne(MediaTag::class, 'item_id', 'id');
    }

}
