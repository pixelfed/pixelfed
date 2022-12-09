<?php

namespace App\Jobs\DeletePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;
use Storage;
use Illuminate\Support\Str;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\PublicTimelineService;
use App\{
	AccountInterstitial,
	AccountLog,
	Avatar,
	Bookmark,
	Collection,
	CollectionItem,
	Contact,
	DirectMessage,
	EmailVerification,
	Follower,
	FollowRequest,
	Hashtag,
	HashtagFollow,
	ImportData,
	ImportJob,
	Like,
	Media,
	MediaTag,
	Mention,
	Notification,
	OauthClient,
	Profile,
	ProfileSponsor,
	Report,
	ReportComment,
	ReportLog,
	StatusHashtag,
	StatusArchived,
	Status,
	Story,
	StoryView,
	User,
	UserDevice,
	UserFilter,
	UserSetting,
};
use App\Models\UserPronoun;

class DeleteAccountPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $user;

	public $timeout = 900;

	public function __construct(User $user)
	{
		$this->user = $user;
	}

	public function handle()
	{
		$user = $this->user;
		$this->deleteUserColumns($user);
		AccountService::del($user->profile_id);

		DB::transaction(function() use ($user) {
			AccountLog::whereItemType('App\User')->whereItemId($user->id)->forceDelete();
		});

		DB::transaction(function() use ($user) {
			AccountInterstitial::whereUserId($user->id)->delete();
		});

		DB::transaction(function() use ($user) {
			if($user->profile) {
				$avatar = $user->profile->avatar;
				$path = $avatar->media_path;
				if(!in_array($path, [
					'public/avatars/default.jpg',
					'public/avatars/default.png'
				])) {
					if(config('pixelfed.cloud_storage')) {
						$disk = Storage::disk(config('filesystems.cloud'));
						if($disk->exists($path)) {
							$disk->delete($path);
						}
					}
					$disk = Storage::disk(config('filesystems.local'));
					if($disk->exists($path)) {
						$disk->delete($path);
					}
				}

				$avatar->forceDelete();
			}

			$id = $user->profile_id;

			ImportData::whereProfileId($id)
				->cursor()
				->each(function($data) {
					$path = storage_path('app/'.$data->path);
					if(is_file($path)) {
						unlink($path);
					}
					$data->delete();
			});
			ImportJob::whereProfileId($id)
				->cursor()
				->each(function($data) {
					$path = storage_path('app/'.$data->media_json);
					if(is_file($path)) {
						unlink($path);
					}
					$data->delete();
			});
			MediaTag::whereProfileId($id)->delete();
			Bookmark::whereProfileId($id)->forceDelete();
			EmailVerification::whereUserId($user->id)->forceDelete();
			StatusHashtag::whereProfileId($id)->delete();
			DirectMessage::whereFromId($id)->orWhere('to_id', $id)->delete();
			StatusArchived::whereProfileId($id)->delete();
			UserPronoun::whereProfileId($id)->delete();
			FollowRequest::whereFollowingId($id)
				->orWhere('follower_id', $id)
				->forceDelete();
			Follower::whereProfileId($id)
				->orWhere('following_id', $id)
				->each(function($follow) {
					FollowerService::remove($follow->profile_id, $follow->following_id);
					$follow->delete();
				});
			FollowerService::delCache($id);
			Like::whereProfileId($id)->forceDelete();
		});

		DB::transaction(function() use ($user) {
			$pid = $this->user->profile_id;

			StoryView::whereProfileId($pid)->delete();
			$stories = Story::whereProfileId($pid)->get();
			foreach($stories as $story) {
				$path = storage_path('app/'.$story->path);
				if(is_file($path)) {
					unlink($path);
				}
				$story->forceDelete();
			}
		});

		DB::transaction(function() use ($user) {
			$medias = Media::whereUserId($user->id)->get();
			foreach($medias as $media) {
				if(config('pixelfed.cloud_storage')) {
					$disk = Storage::disk(config('filesystems.cloud'));
					if($disk->exists($media->media_path)) {
						$disk->delete($media->media_path);
					}
					if($disk->exists($media->thumbnail_path)) {
						$disk->delete($media->thumbnail_path);
					}
				}
				$disk = Storage::disk(config('filesystems.local'));
				if($disk->exists($media->media_path)) {
					$disk->delete($media->media_path);
				}
				if($disk->exists($media->thumbnail_path)) {
					$disk->delete($media->thumbnail_path);
				}
				$media->forceDelete();
			}
		});

		DB::transaction(function() use ($user) {
			Mention::whereProfileId($user->profile_id)->forceDelete();
			Notification::whereProfileId($user->profile_id)
				->orWhere('actor_id', $user->profile_id)
				->forceDelete();
		});

		DB::transaction(function() use ($user) {
			$collections = Collection::whereProfileId($user->profile_id)->get();
			foreach ($collections as $collection) {
				$collection->items()->delete();
				$collection->delete();
			}
			Contact::whereUserId($user->id)->delete();
			HashtagFollow::whereUserId($user->id)->delete();
			OauthClient::whereUserId($user->id)->delete();
			DB::table('oauth_access_tokens')->whereUserId($user->id)->delete();
			DB::table('oauth_auth_codes')->whereUserId($user->id)->delete();
			ProfileSponsor::whereProfileId($user->profile_id)->delete();
		});

		DB::transaction(function() use ($user) {
			Status::whereProfileId($user->profile_id)->forceDelete();
			Report::whereUserId($user->id)->forceDelete();
			PublicTimelineService::warmCache(true, 400);
			$this->deleteProfile($user);
		});
	}

	protected function deleteProfile($user) {
		DB::transaction(function() use ($user) {
			Profile::whereUserId($user->id)->delete();
			$this->deleteUserSettings($user);
		});
	}

	protected function deleteUserSettings($user) {

		DB::transaction(function() use ($user) {
			UserDevice::whereUserId($user->id)->forceDelete();
			UserFilter::whereUserId($user->id)->forceDelete();
			UserSetting::whereUserId($user->id)->forceDelete();
		});
	}

	protected function deleteUserColumns($user)
	{
		DB::transaction(function() use ($user) {
			$user->status = 'deleted';
			$user->name = 'deleted';
			$user->email = $user->id;
			$user->password = '';
			$user->remember_token = null;
			$user->is_admin = false;
			$user->{'2fa_enabled'} = false;
			$user->{'2fa_secret'} = null;
			$user->{'2fa_backup_codes'} = null;
			$user->{'2fa_setup_at'} = null;
			$user->save();
		});
	}
}
