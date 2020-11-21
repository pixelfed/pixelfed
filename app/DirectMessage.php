<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class DirectMessage extends Model
{
    public function status()
    {
    	return $this->hasOne(Status::class, 'id', 'status_id');
    }

    public function url()
    {
    	return config('app.url') . '/account/direct/m/' . $this->status_id;
    }

    public function author()
    {
    	return $this->hasOne(Profile::class, 'id', 'from_id');
    }

    public function recipient()
    {
        return $this->hasOne(Profile::class, 'id', 'to_id');
    }

    public function me()
    {
    	return Auth::user()->profile->id === $this->from_id;
    }

    public function toText()
    {
        $actorName = $this->author->username;

        return "{$actorName} sent a direct message.";
    }

    public function toHtml()
    {
        $actorName = $this->author->username;
        $actorUrl = $this->author->url();
        $url = $this->url();

        return "<a href='{$actorUrl}' class='profile-link'>{$actorName}</a> sent a <a href='{$url}' class='dm-link'>direct message</a>.";
    }
}
