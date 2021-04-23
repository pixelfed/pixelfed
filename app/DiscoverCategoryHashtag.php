<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscoverCategoryHashtag extends Model
{
    protected $fillable = [
    	'discover_category_id',
    	'hashtag_id'
    ];
}
