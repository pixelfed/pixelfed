<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    public function url()
    {
        $base = config('app.url');
        $path = '/i/confirm-email/'.$this->user_token.'/'.$this->random_token;

        return "{$base}{$path}";
    }

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
