<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
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
      return "{$actorName} " . __('notification.likedPhoto');
    }

    public function toHtml()
    {
      $actorName = $this->actor->username;
      $actorUrl = $this->actor->url();
      return "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> " .
          __('notification.likedPhoto');
    }
}
