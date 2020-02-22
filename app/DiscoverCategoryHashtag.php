<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiscoverCategoryHashtag extends Model
{
    protected $fillable = [
    	'discover_category_id',
    	'hashtag_id'
    ];
}
