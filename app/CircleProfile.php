<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class CircleProfile extends Model
{
    protected $fillable = [
    	'circle_id',
    	'profile_id'
    ];
}
