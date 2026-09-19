<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $token
 * @property int $weight
 * @property int $is_spam
 * @property string|null $note
 * @property string|null $category
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereIsSpam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AutospamCustomTokens whereWeight($value)
 *
 * @mixin \Eloquent
 */
class AutospamCustomTokens extends Model
{
    use HasFactory;
}
