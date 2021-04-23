<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;

class UserDevice extends Model
{
	protected $fillable = [
		'user_id',
		'ip',
		'user_agent'
	];

    public $timestamps = [
    	'last_active_at'
    ];

    public function user()
    {
    	return $this->belongsTo(User::class);
    }

    public function getUserAgent()
    {
        if(!$this->user_agent) {
            return 'Unknown';
        }
        $agent = new Agent();
        $agent->setUserAgent($this->user_agent);
        return $agent;
    }
}
