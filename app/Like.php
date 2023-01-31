<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Like extends Model
{
    use SoftDeletes;

    const MAX_PER_DAY = 500;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    protected $fillable = ['profile_id', 'status_id', 'status_profile_id'];

    public function actor()
    {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function toText($type = 'post')
    {
        $actorName = $this->actor->username;
        $msg = $type == 'post' ? __('notification.likedPhoto') : __('notification.likedComment');

        return "{$actorName} ".$msg;
    }

    public function toHtml($type = 'post')
    {
        $actorName = $this->actor->username;
        $actorUrl = $this->actor->url();
        $msg = $type == 'post' ? __('notification.likedPhoto') : __('notification.likedComment');

        return "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> ".$msg;
    }
}
