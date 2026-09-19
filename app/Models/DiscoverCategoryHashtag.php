<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $discover_category_id
 * @property int $hashtag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategoryHashtag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategoryHashtag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategoryHashtag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategoryHashtag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategoryHashtag whereDiscoverCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategoryHashtag whereHashtagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategoryHashtag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiscoverCategoryHashtag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DiscoverCategoryHashtag extends Model
{
    protected $guarded = [];
}
