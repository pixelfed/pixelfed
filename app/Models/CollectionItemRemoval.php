<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionItemRemoval extends Model
{
    protected $fillable = [
        'collection_id',
        'status_id',
        'profile_id',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
