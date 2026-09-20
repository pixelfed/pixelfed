<?php

namespace App\Models;

use App\HasSnowflakePrimary;
use App\Services\FollowerService;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $domain
 * @property string|null $username
 * @property string|null $status
 * @property string|null $name
 * @property string|null $bio
 * @property int $unlisted
 * @property int $cw
 * @property int $no_autolink
 * @property string|null $location
 * @property string|null $website
 * @property string|null $fields
 * @property string|null $profile_layout
 * @property string|null $header_bg
 * @property string|null $post_layout
 * @property int $is_private
 * @property string|null $sharedInbox
 * @property string|null $inbox_url
 * @property string|null $outbox_url
 * @property string|null $key_id
 * @property string|null $follower_url
 * @property string|null $following_url
 * @property string|null $private_key
 * @property string|null $public_key
 * @property string|null $remote_url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property string|null $delete_after
 * @property int $is_suggestable
 * @property Carbon|null $last_fetched_at
 * @property int|null $status_count
 * @property int $followers_count
 * @property int $following_count
 * @property string|null $webfinger
 * @property string|null $avatar_url
 * @property Carbon|null $last_status_at
 * @property int|null $moved_to_profile_id
 * @property int $indexable
 * @property string|null $followers_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProfileAlias> $aliases
 * @property-read int|null $aliases_count
 * @property-read Avatar $avatar
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Status> $bookmarks
 * @property-read int|null $bookmarks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Circle> $circles
 * @property-read int|null $circles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Collection> $collections
 * @property-read int|null $collections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Profile> $followers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Profile> $following
 * @property-read \Illuminate\Database\Eloquent\Collection<int, HashtagFollow> $hashtagFollowing
 * @property-read int|null $hashtag_following_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Hashtag> $hashtags
 * @property-read int|null $hashtags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Like> $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Report> $reported
 * @property-read int|null $reported_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Status> $statuses
 * @property-read int|null $statuses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Story> $stories
 * @property-read int|null $stories_count
 * @property-read User|null $user
 *
 * @method static \Database\Factories\ProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereCw($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereDeleteAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereFollowerUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereFollowersCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereFollowersUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereFollowingCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereFollowingUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereHeaderBg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereInboxUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereIndexable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereIsPrivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereIsSuggestable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereLastFetchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereLastStatusAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereMovedToProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereNoAutolink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereOutboxUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile wherePostLayout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile wherePrivateKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereProfileLayout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile wherePublicKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereRemoteUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereSharedInbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereStatusCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereUnlisted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereWebfinger($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Profile extends Model
{
    use HasFactory, HasSnowflakePrimary, SoftDeletes;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $hidden = ['private_key'];

    protected $visible = ['id', 'user_id', 'username', 'name'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
            'last_fetched_at' => 'datetime',
            'last_status_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url($suffix = null)
    {
        return $this->remote_url ?? url($this->username.$suffix);
    }

    public function localUrl($suffix = null)
    {
        return url($this->username.$suffix);
    }

    public function permalink($suffix = null)
    {
        return $this->remote_url ?? url('users/'.$this->username.$suffix);
    }

    public function emailUrl()
    {
        if ($this->domain) {
            return $this->username;
        }

        $domain = parse_url(config('app.url'), PHP_URL_HOST);

        return $this->username.'@'.$domain;
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class);
    }

    public function followingCount($short = false)
    {
        $count = Cache::remember('profile:following_count:'.$this->id, now()->addMonths(1), function () {
            if ($this->domain == null && $this->user->settings->show_profile_following_count == false) {
                return 0;
            }
            $count = DB::table('followers')->where('profile_id', $this->id)->count();
            if ($this->following_count != $count) {
                $this->following_count = $count;
                $this->save();
            }

            return $count;
        });

        return $short ? PrettyNumber::convert($count) : $count;
    }

    public function followerCount($short = false)
    {
        $count = Cache::remember('profile:follower_count:'.$this->id, now()->addMonths(1), function () {
            if ($this->domain == null && $this->user->settings->show_profile_follower_count == false) {
                return 0;
            }
            $count = DB::table('followers')->where('following_id', $this->id)->count();
            if ($this->followers_count != $count) {
                $this->followers_count = $count;
                $this->save();
            }

            return $count;
        });

        return $short ? PrettyNumber::convert($count) : $count;
    }

    public function statusCount()
    {
        return $this->status_count;
    }

    public function following()
    {
        return $this->belongsToMany(
            self::class,
            'followers',
            'profile_id',
            'following_id'
        );
    }

    public function followers()
    {
        return $this->belongsToMany(
            self::class,
            'followers',
            'following_id',
            'profile_id'
        );
    }

    public function follows($profile): bool
    {
        return Follower::whereProfileId($this->id)->whereFollowingId($profile->id)->exists();
    }

    public function followedBy($profile): bool
    {
        return Follower::whereProfileId($profile->id)->whereFollowingId($this->id)->exists();
    }

    public function bookmarks()
    {
        return $this->belongsToMany(
            Status::class,
            'bookmarks',
            'profile_id',
            'status_id'
        );
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function avatar(): HasOne
    {
        return $this->hasOne(Avatar::class)->withDefault([
            'media_path' => 'public/avatars/default.jpg',
            'change_count' => 0,
        ]);
    }

    public function avatarUrl()
    {
        $url = Cache::remember('avatar:'.$this->id, 1209600, function () {
            $avatar = $this->avatar;

            if (! $avatar) {
                return url('/storage/avatars/default.jpg');
            }

            if ($avatar->cdn_url) {
                if (str_starts_with($avatar->cdn_url, 'https://')) {
                    return $avatar->cdn_url;
                }

                return url('/storage/avatars/default.jpg');
            }

            $path = $avatar->media_path;

            if (! $path) {
                return url('/storage/avatars/default.jpg');
            }

            if (
                $avatar->is_remote &&
                $avatar->remote_url &&
                boolval(config_cache('federation.avatars.store_local')) === true
            ) {
                return $avatar->remote_url;
            }

            if ($path === 'public/avatars/default.jpg') {
                return url('/storage/avatars/default.jpg');
            }

            if (! str_starts_with($path, 'public')) {
                return url('/storage/avatars/default.jpg');
            }

            if (config('filesystems.default') !== 'local') {
                return Storage::url($path);
            }

            $path = "{$path}?v={$avatar->change_count}";

            return url(Storage::url($path));
        });

        return $url;
    }

    // deprecated
    public function recommendFollowers()
    {
        return collect([]);
    }

    public function keyId()
    {
        if ($this->remote_url) {
            return;
        }

        return $this->permalink('#main-key');
    }

    public function mutedIds()
    {
        return UserFilter::whereUserId($this->id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('mute')
            ->pluck('filterable_id');
    }

    public function blockedIds()
    {
        return UserFilter::whereUserId($this->id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->pluck('filterable_id');
    }

    public function mutedProfileUrls()
    {
        $ids = $this->mutedIds();

        return $this->whereIn('id', $ids)->get()->map(function ($i) {
            return $i->url();
        });
    }

    public function blockedProfileUrls()
    {
        $ids = $this->blockedIds();

        return $this->whereIn('id', $ids)->get()->map(function ($i) {
            return $i->url();
        });
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'profile_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'profile_id');
    }

    public function inboxUrl()
    {
        return $this->inbox_url ?? $this->permalink('/inbox');
    }

    public function outboxUrl()
    {
        return $this->outbox_url ?? $this->permalink('/outbox');
    }

    public function sharedInbox()
    {
        return $this->sharedInbox ?? $this->inboxUrl();
    }

    public function getDefaultScope(): string
    {
        return $this->is_private == true ? 'private' : 'public';
    }

    /**
     * @return mixed[][]
     */
    public function getAudience($scope = false): array
    {
        if ($this->remote_url) {
            return [];
        }
        $scope = $scope ?? $this->getDefaultScope();
        $audience = [];
        switch ($scope) {
            case 'public':
                $audience = [
                    'to' => [
                        'https://www.w3.org/ns/activitystreams#Public',
                    ],
                    'cc' => [
                        $this->permalink('/followers'),
                    ],
                ];
                break;

            case 'unlisted':
                $audience = [
                    'to' => [],
                    'cc' => [
                        'https://www.w3.org/ns/activitystreams#Public',
                        $this->permalink('/followers'),
                    ],
                ];
                break;

            case 'private':
                $audience = [
                    'to' => [
                        $this->permalink('/followers'),
                    ],
                    'cc' => [],
                ];
                break;
        }

        return $audience;
    }

    public function getAudienceInbox($scope = 'public')
    {
        return FollowerService::audience($this->id, $scope);
    }

    public function circles()
    {
        return $this->hasMany(Circle::class);
    }

    public function hashtags()
    {
        return $this->hasManyThrough(
            Hashtag::class,
            StatusHashtag::class,
            'profile_id',
            'id',
            'id',
            'hashtag_id'
        );
    }

    public function hashtagFollowing()
    {
        return $this->hasMany(HashtagFollow::class);
    }

    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    public function hasFollowRequestById(int $id)
    {
        return FollowRequest::whereFollowerId($id)
            ->whereFollowingId($this->id)
            ->exists();
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function reported()
    {
        return $this->hasMany(Report::class, 'object_id');
    }

    public function aliases()
    {
        return $this->hasMany(ProfileAlias::class);
    }
}
