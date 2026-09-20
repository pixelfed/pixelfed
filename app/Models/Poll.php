<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $story_id
 * @property int|null $status_id
 * @property int|null $group_id
 * @property int $profile_id
 * @property array<array-key, mixed>|null $poll_options
 * @property array<array-key, mixed>|null $cached_tallies
 * @property int $multiple
 * @property int $hide_totals
 * @property int $votes_count
 * @property string|null $last_fetched_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PollVote> $votes
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereCachedTallies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereHideTotals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereLastFetchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereMultiple($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll wherePollOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereStoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Poll whereVotesCount($value)
 *
 * @mixin \Eloquent
 */
class Poll extends Model
{
    use HasFactory, HasSnowflakePrimary;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'poll_options' => 'array',
            'cached_tallies' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    public function getTallies()
    {
        return $this->cached_tallies;
    }
}
