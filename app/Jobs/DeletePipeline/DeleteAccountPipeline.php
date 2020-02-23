<?php

namespace App\Jobs\DeletePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;
use Illuminate\Support\Str;
use App\AccountLog;
use App\Activity;
use App\Avatar;
use App\Bookmark;
use App\Collection;
use App\CollectionItem;
use App\Contact;
use App\DirectMessage;
use App\EmailVerification;
use App\Follower;
use App\FollowRequest;
use App\Hashtag;
use App\HashtagFollow;
use App\Like;
use App\Media;
use App\Mention;
use App\Notification;
use App\OauthClient;
use App\Profile;
use App\ProfileSponsor;
use App\Report;
use App\ReportComment;
use App\ReportLog;
use App\StatusHashtag;
use App\Status;
use App\Story;
use App\StoryView;
use App\User;
use App\UserDevice;
use App\UserFilter;
use App\UserSetting;

class DeleteAccountPipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $user;

	public function __construct(User $user)
	{
		$this->user = $user;
	}

	public function handle()
	{
		$user = $this->user;

		DB::transaction(function() use ($user) {
			AccountLog::chunk(200, function($logs) use ($user) {
				foreach($logs as $log) {
					if($log->user_id == $user->id) {
						$log->forceDelete();
					}
				}
			});
		});

		DB::transaction(function() use ($user) {
			if($user->profile) {
				$avatar = $user->profile->avatar;
				$avatar->forceDelete();
			}

			$id = $user->profile_id;

			Bookmark::whereProfileId($user->profile_id)->forceDelete();
			EmailVerification::whereUserId($user->id)->forceDelete();
			StatusHashtag::whereProfileId($id)->delete();
			FollowRequest::whereFollowingId($id)
				->orWhere('follower_id', $id)
				->forceDelete();
			Follower::whereProfileId($id)
				->orWhere('following_id', $id)
				->forceDelete();
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
				$path = storage_path('app/'.$media->media_path);
				$thumb = storage_path('app/'.$media->thumbnail_path);
				if(is_file($path)) {
					unlink($path);
				}
				if(is_file($thumb)) {
					unlink($thumb);
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
			ProfileSponsor::whereProfileId($user->profile_id)->delete();
		});

		DB::transaction(function() use ($user) {
			Status::whereProfileId($user->profile_id)->forceDelete();
			Report::whereUserId($user->id)->forceDelete();
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
			$this->deleteUserColumns($user);
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
