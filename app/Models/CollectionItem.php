<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $collection_id
 * @property int|null $order
 * @property string $object_type
 * @property int $object_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Collection|null $collection
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem whereCollectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CollectionItem whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
