<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Pixelfed\Snowflake\HasSnowflakePrimary;

class Collection extends Model
{
	use HasSnowflakePrimary;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public $fillable = ['profile_id', 'published_at'];

    public $dates = ['published_at'];

	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}

    public function items()
    {
        return $this->hasMany(CollectionItem::class);
    }

    public function posts()
    {
        return $this->hasManyThrough(
            Status::class,
            CollectionItem::class,
            'collection_id',
            'id',
            'id',
            'object_id'
        );
    }

    public function url()
    {
        return url("/c/{$this->id}");
    }
}
