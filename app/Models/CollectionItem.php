<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(incrementing: false)]
#[Fillable('collection_id', 'object_type', 'object_id', 'order')]
class CollectionItem extends Model
{
    use HasSnowflakePrimary;

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
