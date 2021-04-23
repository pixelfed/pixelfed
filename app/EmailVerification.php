<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

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
