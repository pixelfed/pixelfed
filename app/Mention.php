<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mention extends Model
{

    public function profile()
    {
      return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }

    public function status()
    {
      return $this->belongsTo(Status::class);
    }

    public function toText()
    {
      $actorName = $this->status->profile->username;
      return "{$actorName} " . __('notification.mentionedYou');
    }

    public function toHtml()
    {
      $actorName = $this->status->profile->username;
      $actorUrl = $this->status->profile->url();
      return "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> " .
          __('notification.mentionedYou');
    }
}
