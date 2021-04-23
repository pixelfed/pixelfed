<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OauthClient extends Model
{

	protected $table = 'oauth_clients';

	public function user()
	{
		return $this->belongsTo(User::class);
	}

}
