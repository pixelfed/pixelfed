<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Story;
use App\StoryView;

class StoryService
{
	const STORY_KEY = 'pf:services:stories:v1:';

	public static function get($id) 
	{
		$account = AccountService::get($id);
		if(!$account) {
			return false;
		}

		$res = [
			'profile' => [
				'id' => (string) $account['id'],
				'avatar' => $account['avatar'],
				'username' => $account['username'],
				'url' => $account['url']
			]
		];

		$res['stories'] = self::getStories($id);
		return $res;
	}

	public static function getStories($id, $pid)
	{
		return Story::whereProfileId($id)
			->latest()
			->get()
			->map(function($s) use($pid) {
				return [
					'id' => (string) $s->id,
					'type' => $s->type,
					'duration' => 10,
					'seen' => in_array($pid, self::views($s->id)),
					'created_at' => $s->created_at->toAtomString(),
					'expires_at' => $s->expires_at->toAtomString(),
					'media' => url(Storage::url($s->path)),
					'can_reply' => (bool) $s->can_reply,
					'can_react' => (bool) $s->can_react,
					'poll' => $s->type == 'poll' ? PollService::storyPoll($s->id) : null
				];
			})
			->toArray();
	}

	public static function views($id)
	{
		return StoryView::whereStoryId($id)
			->pluck('profile_id')
			->toArray();
	}

	public static function hasSeen($pid, $sid)
	{
		$key = self::STORY_KEY . 'seen:' . $pid . ':' . $sid;
		return Cache::remember($key, 3600, function() use($pid, $sid) {
			return StoryView::whereStoryId($sid)
			->whereProfileId($pid)
			->exists();
		});
	}

	public static function latest($pid)
	{
		return Cache::remember(self::STORY_KEY . 'latest:pid-' . $pid, 3600, function() use ($pid) {
			return Story::whereProfileId($pid)
				->latest()
				->first()
				->id;
		});
	}

	public static function delLatest($pid)
	{
		return Cache::forget(self::STORY_KEY . 'latest:pid-' . $pid);
	}

	public static function addSeen($pid, $sid)
	{
		return Cache::put(self::STORY_KEY . 'seen:' . $pid . ':' . $sid, true, 86400);
	}

	public static function adminStats()
	{
		return Cache::remember('pf:admin:stories:stats', 300, function() {
			$total = Story::count();
			return [
				'active' => [
					'today' => Story::whereDate('created_at', now()->today())->count(),
					'month' => Story::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()
				],
				'total' => $total,
				'remote' => [
					'today' => Story::whereLocal(false)->whereDate('created_at', now()->today())->count(),
					'month' => Story::whereLocal(false)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count()
				],
				'storage' => [
					'sum' => (int) Story::sum('size'),
					'average' => (int) Story::avg('size')
				],
				'avg_spu' => (int) ($total / Story::groupBy('profile_id')->pluck('profile_id')->count()),
				'avg_duration' => (int) floor(Story::avg('duration')),
				'avg_type' => Story::selectRaw('type, count(id) as count')->groupBy('type')->orderByDesc('count')->first()->type
			];
		});
	}
}
