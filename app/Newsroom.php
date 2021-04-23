<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Newsroom extends Model
{
    protected $table = 'newsroom';
    protected $fillable = ['title'];

    protected $dates = ['published_at'];

    public function permalink()
    {
    	$year = $this->published_at->year;
    	$month = $this->published_at->format('m');
    	$slug = $this->slug;

    	return url("/site/newsroom/{$year}/{$month}/{$slug}");
    }

    public function editUrl()
    {
        return url("/i/admin/newsroom/edit/{$this->id}");
    }
}
