<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $profile_url
 * @property int|null $profile_id
 * @property string|null $domain
 * @property string|null $note
 * @property int $is_banned
 * @property int $is_nsfw
 * @property int $is_unlisted
 * @property int $is_noautolink
 * @property int $is_nodms
 * @property int $is_notrending
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereIsBanned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereIsNoautolink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereIsNodms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereIsNotrending($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereIsNsfw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereIsUnlisted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereProfileUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModeratedProfile whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ModeratedProfile extends Model
{
    use HasFactory;

    public $guarded = [];
}
