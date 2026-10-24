<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use App\Util\Lexer\Bearcap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $profile_id
 * @property string|null $type
 * @property int|null $size
 * @property string|null $mime
 * @property int $duration
 * @property string|null $path
 * @property string|null $remote_url
 * @property string|null $media_url
 * @property string|null $cdn_url
 * @property int $public
 * @property int $local
 * @property int $view_count
 * @property int|null $comment_count
 * @property array<array-key, mixed>|null $story
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $is_archived
 * @property string|null $name
 * @property int|null $active
 * @property int $can_reply
 * @property int $can_react
 * @property string|null $object_id
 * @property string|null $object_uri
 * @property string|null $bearcap_token
 * @property-read Profile|null $profile
 * @property-read Collection<int, StoryView> $views
 * @property-read int|null $views_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story toAudience()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereBearcapToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereCanReact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereCanReply($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereCdnUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereCommentCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereIsArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereMediaUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereObjectUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story wherePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereStory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Story whereViewCount($value)
 *
 * @mixin \Eloquent
 */
class Story extends Model
{
    use HasSnowflakePrimary;

    public const MAX_PER_DAY = 20;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $guarded = [];

    protected $visible = ['id'];

    protected $hidden = ['json'];

    protected function casts(): array
    {
        return [
            'story' => 'json',
            'expires_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function views()
    {
        return $this->hasMany(StoryView::class);
    }

    public function seen($pid = false)
    {
        return StoryView::whereStoryId($this->id)
            ->whereProfileId(Auth::user()->profile->id)
            ->exists();
    }

    public function permalink()
    {
        $username = $this->profile->username;

        return url("/stories/{$username}/{$this->id}/activity");
    }

    public function url()
    {
        $username = $this->profile->username;

        return url("/stories/{$username}/{$this->id}");
    }

    public function mediaUrl()
    {
        return url(Storage::url($this->path));
    }

    public function bearcapUrl()
    {
        return Bearcap::encode($this->url(), $this->bearcap_token);
    }

    /**
     * @return list
     */
    public function scopeToAudience($scope): array
    {
        $res = [];

        switch ($scope) {
            case 'to':
                $res = [
                    $this->profile->permalink('/followers'),
                ];
                break;

            default:
                $res = [];
                break;
        }

        return $res;
    }

    public function toAdminEntity(): array
    {
        return [
            'id' => $this->id,
            'profile_id' => $this->profile_id,
            'media_src' => $this->mediaUrl(),
            'url' => $this->url(),
            'type' => $this->type,
            'duration' => $this->duration,
            'mime' => $this->mime,
            'size' => $this->size,
            'local' => $this->local,
        ];
    }
}
