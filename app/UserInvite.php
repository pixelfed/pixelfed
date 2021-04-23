<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserInvite extends Model
{
	public function sender()
	{
		return $this->belongsTo(Profile::class, 'profile_id');
	}

    public function url()
    {
    	return url("/i/invite/code/{$this->key}/{$this->token}");
    }
}
