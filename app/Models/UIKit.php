<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $k
 * @property string|null $v
 * @property string|null $meta
 * @property string|null $defv
 * @property string|null $dhis
 * @property int|null $edit_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereDefv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereDhis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereEditCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereK($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UIKit whereV($value)
 *
 * @mixin \Eloquent
 */
class UIKit extends Model
{
    protected $table = 'uikit';

    protected $guarded = [];

    public static function section($k)
    {
        return (new self)->where('k', $k)->first()->v;
    }
}
