<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Model;

class CollectionItem extends Model
{
    use HasSnowflakePrimary;

    public $fillable = [
        'collection_id',
        'object_type',
        'object_id',
        'order',
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
