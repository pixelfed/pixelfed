<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    public function actor()
    {
      return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function target()
    {
      return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function profile()
    {
      return $this->belongsTo(Profile::class, 'following_id', 'id');
    }

    public function toText()
    {
      $actorName = $this->actor->username;
      return "{$actorName} " . __('notification.startedFollowingYou');
    }

    public function toHtml()
    {
      $actorName = $this->actor->username;
      $actorUrl = $this->actor->url();
      return "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> " .
          __('notification.startedFollowingYou');
    }
}
