<?php

namespace App;

use Auth, Cache, DB, Storage;
use App\Util\Lexer\PrettyNumber;
use App\HasSnowflakePrimary;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use App\Services\FollowerService;

class Profile extends Model
{
	use HasSnowflakePrimary, SoftDeletes;

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var bool
	 */
	public $incrementing = false;

	protected $dates = [
		'deleted_at',
		'last_fetched_at',
		'last_status_at'
	];
	protected $hidden = ['private_key'];
	protected $visible = ['id', 'user_id', 'username', 'name'];
	protected $guarded = [];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function url($suffix = null)
	{
		return $this->remote_url ?? url($this->username . $suffix);
	}

	public function localUrl($suffix = null)
	{
		return url($this->username . $suffix);
	}

	public function permalink($suffix = null)
	{
		return $this->remote_url ?? url('users/' . $this->username . $suffix);
	}

	public function emailUrl()
	{
		if($this->domain) {
			return $this->username;
		}

		$domain = parse_url(config('app.url'), PHP_URL_HOST);

		return $this->username.'@'.$domain;
	}

	public function statuses()
	{
		return $this->hasMany(Status::class);
	}

	public function followingCount($short = false)
	{
		$count = Cache::remember('profile:following_count:'.$this->id, now()->addMonths(1), function() {
			if($this->domain == null && $this->user->settings->show_profile_following_count == false) {
				return 0;
			}
			$count = DB::table('followers')->where('profile_id', $this->id)->count();
			if($this->following_count != $count) {
				$this->following_count = $count;
				$this->save();
			}
			return $count;
		});

		return $short ? PrettyNumber::convert($count) : $count;
	}

	public function followerCount($short = false)
	{
		$count = Cache::remember('profile:follower_count:'.$this->id, now()->addMonths(1), function() {
			if($this->domain == null && $this->user->settings->show_profile_follower_count == false) {
				return 0;
			}
			$count = DB::table('followers')->where('following_id', $this->id)->count();
			if($this->followers_count != $count) {
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

	public function follows($profile) : bool
	{
		return Follower::whereProfileId($this->id)->whereFollowingId($profile->id)->exists();
	}

	public function followedBy($profile) : bool
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

	public function avatar()
	{
		return $this->hasOne(Avatar::class)->withDefault([
			'media_path' => 'public/avatars/default.jpg',
			'change_count' => 0
		]);
	}

	public function avatarUrl()
	{
		$url = Cache::remember('avatar:'.$this->id, 1209600, function () {
			$avatar = $this->avatar;

			if($avatar->cdn_url) {
				if(substr($avatar->cdn_url, 0, 8) === 'https://') {
					return $avatar->cdn_url;
				} else {
					return url('/storage/avatars/default.jpg');
				}
			}

			$path = $avatar->media_path;

			if(substr($path, 0, 6) !== 'public') {
				return url('/storage/avatars/default.jpg');
			}

			$path = "{$path}?v={$avatar->change_count}";

			return config('app.url') . Storage::url($path);
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
			->whereFilterableType('App\Profile')
			->whereFilterType('mute')
			->pluck('filterable_id');
	}

	public function blockedIds()
	{
		return UserFilter::whereUserId($this->id)
			->whereFilterableType('App\Profile')
			->whereFilterType('block')
			->pluck('filterable_id');
	}

	public function mutedProfileUrls()
	{
		$ids = $this->mutedIds();
		return $this->whereIn('id', $ids)->get()->map(function($i) {
			return $i->url();
		});
	}

	public function blockedProfileUrls()
	{
		$ids = $this->blockedIds();
		return $this->whereIn('id', $ids)->get()->map(function($i) {
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

	public function getDefaultScope()
	{
		return $this->is_private == true ? 'private' : 'public';
	}

	public function getAudience($scope = false)
	{
		if($this->remote_url) {
			return [];
		}
		$scope = $scope ?? $this->getDefaultScope();
		$audience = [];
		switch ($scope) {
			case 'public':
				$audience = [
					'to' => [
						'https://www.w3.org/ns/activitystreams#Public'
					],
					'cc' => [
						$this->permalink('/followers')
					]
				];
			break;

			case 'unlisted':
				$audience = [
					'to' => [
					],
					'cc' => [
						'https://www.w3.org/ns/activitystreams#Public',
						$this->permalink('/followers')
					]
				];
			break;

			case 'private':
				$audience = [
					'to' => [
						$this->permalink('/followers')
					],
					'cc' => [
					]
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
}
