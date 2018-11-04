<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Like extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    protected $fillable = ['profile_id', 'status_id'];

    public function actor()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function toText()
    {
        $actorName = $this->actor->username;

        return "{$actorName} ".__('notification.likedPhoto');
    }

    public function toHtml()
    {
        $actorName = $this->actor->username;
        $actorUrl = $this->actor->url();

        return "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> ".
          __('notification.likedPhoto');
    }
}
