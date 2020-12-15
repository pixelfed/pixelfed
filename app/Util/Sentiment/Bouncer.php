<?php

namespace App\Util\Sentiment;

use App\AccountInterstitial;
use App\Status;
use Cache;
use Illuminate\Support\Str;

class Bouncer {

	public static function get(Status $status)
	{
		if($status->uri || $status->scope != 'public') {
			return;
		}

		$recentKey = 'pf:bouncer:recent_by_pid:' . $status->profile_id;
		$recentTtl = now()->addMinutes(5);
		$recent = Cache::remember($recentKey, $recentTtl, function() use($status) {
			return $status->profile->created_at->gt(now()->subMonths(3)) || $status->profile->statuses()->count() == 0;
		});

		if(!$recent) {
			return;
		}
		
		if($status->profile->followers()->count() > 100) {
			return;
		}

		if(!Str::contains($status->caption, ['https://', 'http://', 'hxxps://', 'hxxp://', 'www.', '.com', '.net', '.org'])) {
			return;
		}

		if($status->profile->user->is_admin == true) {
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
		$status->is_nsfw = true;
		$status->save();

	}

}