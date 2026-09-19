<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $sha256
 * @property string|null $sha512
 * @property string|null $name
 * @property string|null $description
 * @property int $active
 * @property string|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereSha256($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereSha512($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MediaBlocklist whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class MediaBlocklist extends Model
{
    //
}
