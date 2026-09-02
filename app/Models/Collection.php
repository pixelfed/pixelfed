<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(incrementing: false)]
#[Fillable('profile_id', 'published_at')]
class Collection extends Model
{
    use HasSnowflakePrimary;

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
