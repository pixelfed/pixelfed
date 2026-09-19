<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $story_id
 * @property int|null $status_id
 * @property int $profile_id
 * @property int $poll_id
 * @property int $choice
 * @property string|null $uri
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote whereChoice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote wherePollId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote whereStoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PollVote whereUri($value)
 *
 * @mixin \Eloquent
 */
class PollVote extends Model
{
    use HasFactory;
}
