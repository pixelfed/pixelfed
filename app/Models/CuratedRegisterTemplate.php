<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $content
 * @property bool $is_active
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CuratedRegisterTemplate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CuratedRegisterTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
