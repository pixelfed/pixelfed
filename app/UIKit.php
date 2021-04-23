<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class UIKit extends Model
{
    protected $table = 'uikit';
    protected $fillable = [
    	'k',
    	'v',
    	'defv',
    	'dhis'
    ];

    public static function section($k)
    {
    	return (new self)->where('k', $k)->first()->v;
    }
}
