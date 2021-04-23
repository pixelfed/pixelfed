<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault  
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use Pixelfed\Snowflake\HasSnowflakePrimary;

class CollectionItem extends Model
{
	use HasSnowflakePrimary;

    public $fillable = [
        'collection_id',
        'object_type',
        'object_id',
        'order'
    ];
    
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    
	public function collection()
	{
		return $this->belongsTo(Collection::class);
	}
}
