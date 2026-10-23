<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $domain
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefaultDomainBlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefaultDomainBlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefaultDomainBlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefaultDomainBlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefaultDomainBlock whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefaultDomainBlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefaultDomainBlock whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DefaultDomainBlock whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class DefaultDomainBlock extends Model
{
    use HasFactory;

    protected $guarded = [];
}
