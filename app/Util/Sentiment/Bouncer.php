<?php

namespace App\Util\Sentiment;

use App\AccountInterstitial;
use App\Status;
use Cache;
use Illuminate\Support\Str;
use App\Services\StatusService;

class Bouncer {

	public static function get(Status $status)
	{
		if($status->uri || $status->scope != 'public') {
			return;
		}

		if($status->profile->user->is_admin == true) {
			return;
		}

		$exemptionKey = 'pf:bouncer_v0:exemption_by_pid:' . $status->profile_id;
		$exemptionTtl = now()->addDays(12);

		if( $status->in_reply_to_id != null && 
			$status->in_reply_to_profile_id == $status->profile_id
		) {
			return;
		}

		$exemption = Cache::remember($exemptionKey, $exemptionTtl, function() use($status) {
			$uid = $status->profile->user_id;
			$ids = AccountInterstitial::whereUserId($uid)
				->whereType('post.autospam')
				->whereItemType('App\Status')
				->whereNotNull('appeal_handled_at')
				->latest()
				->take(5)
				->pluck('item_id');

			if($ids->count() == 0) {
				return false;
			}

			$count = Status::select('id', 'scope')
				->whereScope('public')
				->find($ids)
				->count();

			return $count >= 1 ? true : false;
		});

		if($exemption == true) {
			return;
		}

		if( $status->profile->created_at->gt(now()->subMonths(6)) &&
			$status->profile->bio &&
			$status->profile->website
		) {
			return (new self)->handle($status);
		}

		$recentKey = 'pf:bouncer_v0:recent_by_pid:' . $status->profile_id;
		$recentTtl = now()->addHours(28);

		$recent = Cache::remember($recentKey, $recentTtl, function() use($status) {
			return $status
				->profile
				->created_at
				->gt(now()->subMonths(6)) || 
			$status
				->profile
				->statuses()
				->whereScope('public')
				->count() == 0;
		});
		
		if(!$recent) {
			return;
		}
		
		if($status->profile->followers()->count() > 100) {
			return;
		}

		if(!Str::contains($status->caption, [
			'https://', 
			'http://', 
			'hxxps://', 
			'hxxp://', 
			'www.', 
			'.com', 
			'.net', 
			'.org'
		])) {
			return;
		}

		return (new self)->handle($status);
	}

	protected function handle($status)
	{
		$media = $status->media;

		$ai = new AccountInterstitial;
		$ai->user_id = $status->profile->user_id;
		$ai->type = 'post.autospam';
		$ai->view = 'account.moderation.post.autospam';
		$ai->item_type = 'App\Status';
		$ai->item_id = $status->id;
		$ai->has_media = (bool) $media->count();
		$ai->blurhash = $media->count() ? $media->first()->blurhash : null;
		$ai->meta = json_encode([
			'caption' => $status->caption,
			'created_at' => $status->created_at,
			'type' => $status->type,
			'url' => $status->url(),
			'is_nsfw' => $status->is_nsfw,
			'scope' => $status->scope,
			'reblog' => $status->reblog_of_id,
			'likes_count' => $status->likes_count,
			'reblogs_count' => $status->reblogs_count,
		]);
		$ai->save();

		$u = $status->profile->user;
		$u->has_interstitial = true;
		$u->save();

		$status->scope = 'unlisted';
		$status->visibility = 'unlisted';
		// $status->is_nsfw = true;
		$status->save();

		StatusService::del($status->id);

		Cache::forget('pf:bouncer_v0:exemption_by_pid:' . $status->profile_id);
		Cache::forget('pf:bouncer_v0:recent_by_pid:' . $status->profile_id);
	}

}
