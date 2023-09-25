<?php

namespace App;

use Auth, Cache, Hashids, Storage;
use Illuminate\Database\Eloquent\Model;
use App\HasSnowflakePrimary;
use App\Http\Controllers\StatusController;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Poll;
use App\Services\AccountService;
use App\Services\StatusService;
use App\Models\StatusEdit;
use Illuminate\Support\Str;

class Status extends Model
{
	use HasSnowflakePrimary, SoftDeletes;

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var bool
	 */
	public $incrementing = false;

	/**
	 * The attributes that should be mutated to dates.
	 *
	 * @var array
	 */
	protected $casts = [
		'deleted_at' => 'datetime',
		'edited_at'  => 'datetime'
	];

	protected $guarded = [];

	const STATUS_TYPES = [
		'text',
		'photo',
		'photo:album',
		'video',
		'video:album',
		'photo:video:album',
		'share',
		'reply',
		'story',
		'story:reply',
		'story:reaction',
		'story:live',
		'loop'
	];

	const MAX_MENTIONS = 20;

	const MAX_HASHTAGS = 60;

	const MAX_LINKS = 5;

	public function profile()
	{
		return $this->belongsTo(Profile::class);
	}

	public function media()
	{
		return $this->hasMany(Media::class);
	}

	public function firstMedia()
	{
		return $this->hasMany(Media::class)->orderBy('order', 'asc')->first();
	}

	public function viewType()
	{
		if($this->type) {
			return $this->type;
		}
		return $this->setType();
	}

	public function setType()
	{
		if(in_array($this->type, self::STATUS_TYPES)) {
			return $this->type;
		}
		$mimes = $this->media->pluck('mime')->toArray();
		$type = StatusController::mimeTypeCheck($mimes);
		if($type) {
			$this->type = $type;
			$this->save();
			return $type;
		}
	}

	public function thumb($showNsfw = false)
	{
		$entity = StatusService::get($this->id, false);

		if(!$entity || !isset($entity['media_attachments']) || empty($entity['media_attachments'])) {
			return url(Storage::url('public/no-preview.png'));
		}

		if((!isset($entity['sensitive']) || $entity['sensitive']) && !$showNsfw) {
			return url(Storage::url('public/no-preview.png'));
		}

        if(!isset($entity['visibility']) || !in_array($entity['visibility'], ['public', 'unlisted'])) {
            return url(Storage::url('public/no-preview.png'));
        }

		return collect($entity['media_attachments'])
            ->filter(fn($media) => $media['type'] == 'image' && in_array($media['mime'], ['image/jpeg', 'image/png']))
            ->map(function($media) {
                if(!Str::endsWith($media['preview_url'], ['no-preview.png', 'no-preview.jpg'])) {
                    return $media['preview_url'];
                }

                return $media['url'];
            })
            ->first() ?? url(Storage::url('public/no-preview.png'));
	}

	public function url($forceLocal = false)
	{
		if($this->uri) {
			return $forceLocal ? "/i/web/post/_/{$this->profile_id}/{$this->id}" : $this->uri;
		} else {
			$id = $this->id;
			$account = AccountService::get($this->profile_id, true);
			if(!$account || !isset($account['username'])) {
				return '/404';
			}
			$path = url(config('app.url')."/p/{$account['username']}/{$id}");
			return $path;
		}
	}

	public function permalink($suffix = '/activity')
	{
		$id = $this->id;
		$username = $this->profile->username;
		$path = config('app.url')."/p/{$username}/{$id}{$suffix}";

		return url($path);
	}

	public function editUrl()
	{
		return $this->url().'/edit';
	}

	public function mediaUrl()
	{
		$media = $this->firstMedia();
		$path = $media->media_path;
		$hash = is_null($media->processed_at) ? md5('unprocessed') : md5($media->created_at);
		$url = $media->cdn_url ? $media->cdn_url . "?v={$hash}" : url(Storage::url($path)."?v={$hash}");

		return $url;
	}

	public function likes()
	{
		return $this->hasMany(Like::class);
	}

	public function liked() : bool
	{
		if(!Auth::check()) {
			return false;
		}

		$pid = Auth::user()->profile_id;

		return Like::select('status_id', 'profile_id')
			->whereStatusId($this->id)
			->whereProfileId($pid)
			->exists();
	}

	public function likedBy()
	{
		return $this->hasManyThrough(
			Profile::class,
			Like::class,
			'status_id',
			'id',
			'id',
			'profile_id'
		);
	}

	public function comments()
	{
		return $this->hasMany(self::class, 'in_reply_to_id');
	}

	public function bookmarked()
	{
		if (!Auth::check()) {
			return false;
		}
		$profile = Auth::user()->profile;

		return Bookmark::whereProfileId($profile->id)->whereStatusId($this->id)->count();
	}

	public function shares()
	{
		return $this->hasMany(self::class, 'reblog_of_id');
	}

	public function shared() : bool
	{
		if(!Auth::check()) {
			return false;
		}
		$pid = Auth::user()->profile_id;

		return $this->select('profile_id', 'reblog_of_id')
			->whereProfileId($pid)
			->whereReblogOfId($this->id)
			->exists();
	}

	public function sharedBy()
	{
		return $this->hasManyThrough(
			Profile::class,
			Status::class,
			'reblog_of_id',
			'id',
			'id',
			'profile_id'
		);
	}

	public function parent()
	{
		$parent = $this->in_reply_to_id ?? $this->reblog_of_id;
		if (!empty($parent)) {
			return $this->findOrFail($parent);
		} else {
			return false;
		}
	}

	public function conversation()
	{
		return $this->hasOne(Conversation::class);
	}

	public function hashtags()
	{
		return $this->hasManyThrough(
		Hashtag::class,
		StatusHashtag::class,
		'status_id',
		'id',
		'id',
		'hashtag_id'
	  );
	}

	public function mentions()
	{
		return $this->hasManyThrough(
		Profile::class,
		Mention::class,
		'status_id',
		'id',
		'id',
		'profile_id'
	  );
	}

	public function reportUrl()
	{
		return route('report.form')."?type=post&id={$this->id}";
	}

	public function toActivityStream()
	{
		$media = $this->media;
		$mediaCollection = [];
		foreach ($media as $image) {
			$mediaCollection[] = [
		  'type'      => 'Link',
		  'href'      => $image->url(),
		  'mediaType' => $image->mime,
		];
		}
		$obj = [
		'@context' => 'https://www.w3.org/ns/activitystreams',
		'type'     => 'Image',
		'name'     => null,
		'url'      => $mediaCollection,
	  ];

		return $obj;
	}

	public function recentComments()
	{
		return $this->comments()->orderBy('created_at', 'desc')->take(3);
	}

	public function toActivityPubObject()
	{
		if($this->local == false) {
			return;
		}
		$profile = $this->profile;
		$to = $this->scopeToAudience('to');
		$cc = $this->scopeToAudience('cc');
		return [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id'    => $this->permalink(),
			'type'  => 'Create',
			'actor' => $profile->permalink(),
			'published' => str_replace('+00:00', 'Z', $this->created_at->format(DATE_RFC3339_EXTENDED)),
			'to' => $to,
			'cc' => $cc,
			'object' => [
				'id' => $this->url(),
				'type' => 'Note',
				'summary' => null,
				'inReplyTo' => null,
				'published' => str_replace('+00:00', 'Z', $this->created_at->format(DATE_RFC3339_EXTENDED)),
				'url' => $this->url(),
				'attributedTo' => $this->profile->url(),
				'to' => $to,
				'cc' => $cc,
				'sensitive' => (bool) $this->is_nsfw,
				'content' => $this->rendered,
				'attachment' => $this->media->map(function($media) {
					return [
						'type' => 'Document',
						'mediaType' => $media->mime,
						'url' => $media->url(),
						'name' => null
					];
				})->toArray()
			]
		];
	}

	public function scopeToAudience($audience)
	{
		if(!in_array($audience, ['to', 'cc']) || $this->local == false) { 
			return;
		}
		$res = [];
		$res['to'] = [];
		$res['cc'] = [];
		$scope = $this->scope;
		$mentions = $this->mentions->map(function ($mention) {
			return $mention->permalink();
		})->toArray();

		if($this->in_reply_to_id != null) {
			$parent = $this->parent();
			if($parent) {
				$mentions = array_merge([$parent->profile->permalink()], $mentions);
			}
		}

		switch ($scope) {
			case 'public':
				$res['to'] = [
					"https://www.w3.org/ns/activitystreams#Public"
				];
				$res['cc'] = array_merge([$this->profile->permalink('/followers')], $mentions);
				break;

			case 'unlisted':
				$res['to'] = array_merge([$this->profile->permalink('/followers')], $mentions);
				$res['cc'] = [
					"https://www.w3.org/ns/activitystreams#Public"
				];
				break;

			case 'private':
				$res['to'] = array_merge([$this->profile->permalink('/followers')], $mentions);
				$res['cc'] = [];
				break;

			// TODO: Update scope when DMs are supported
			case 'direct':
				$res['to'] = [];
				$res['cc'] = [];
				break;
		}
		return $res[$audience];
	}

	public function place()
	{
		return $this->belongsTo(Place::class);
	}

	public function directMessage()
	{
		return $this->hasOne(DirectMessage::class);
	}

	public function poll()
	{
		return $this->hasOne(Poll::class);
	}

	public function edits()
	{
		return $this->hasMany(StatusEdit::class);
	}
}
