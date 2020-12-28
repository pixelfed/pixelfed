<?php

namespace App\Jobs\DeletePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DB;
use Illuminate\Support\Str;
use App\{
	AccountInterstitial,
	AccountLog,
	Activity,
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
	Status,
	Story,
	StoryView,
	User,
	UserDevice,
	UserFilter,
	UserSetting,
};

class DeleteRemoteProfilePipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $profile;

	public function __construct(Profile $profile)
	{
		$this->profile = $profile;
	}

	public function handle()
	{
		$profile = $this->profile;

		if($profile->domain == null || $profile->private_key) {
			return;
		}

		DB::transaction(function() use ($profile) {
			$profile->avatar->forceDelete();

			$id = $profile->id;

			MediaTag::whereProfileId($id)->delete();
			StatusHashtag::whereProfileId($id)->delete();
			DirectMessage::whereFromId($id)->delete();
			FollowRequest::whereFollowingId($id)
				->orWhere('follower_id', $id)
				->forceDelete();
			Follower::whereProfileId($id)
				->orWhere('following_id', $id)
				->forceDelete();
			Like::whereProfileId($id)->forceDelete();
		});

		DB::transaction(function() use ($profile) {
			$pid = $profile->id;
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

		DB::transaction(function() use ($profile) {
			$medias = Media::whereProfileId($profile->id)->get();
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

		DB::transaction(function() use ($profile) {
			Mention::whereProfileId($profile->id)->forceDelete();
			Notification::whereProfileId($profile->id)
				->orWhere('actor_id', $profile->id)
				->forceDelete();
		});

		DB::transaction(function() use ($profile) {
			Status::whereProfileId($profile->id)
				->cursor()
				->each(function($status) {
					AccountInterstitial::where('item_type', 'App\Status')
						->where('item_id', $status->id)
						->delete();
					$status->forceDelete();
				});
			Report::whereProfileId($profile->id)->forceDelete();
			$this->deleteProfile($profile);
		});
	}

	protected function deleteProfile($profile) {
		DB::transaction(function() use ($profile) {
			Profile::findOrFail($profile->id)->delete();
		});
	}
}
