<?php

namespace App;

use Auth;
use Illuminate\Database\Eloquent\Model;

class DirectMessage extends Model
{
    public function status()
    {
    	return $this->belongsTo(Status::class, 'status_id', 'id');
    }

    public function url()
    {
    	return config('app.url') . '/account/direct/m/' . $this->status_id;
    }

    public function author()
    {
    	return $this->belongsTo(Profile::class, 'from_id', 'id');
    }

    public function recipient()
    {
        return $this->belongsTo(Profile::class, 'to_id', 'id');
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

        return "{$actorName} sent a direct message.";
    }
}
