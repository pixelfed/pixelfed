<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProfileSponsor extends Model
{
    public $fillable = ['profile_id'];

    public function profile()
    {
    	return $this->belongsTo(Profile::class);
    }
}
