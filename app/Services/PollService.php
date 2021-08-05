<?php

namespace App\Services;

use App\Models\Poll;
use App\Models\PollVote;
use App\Status;
use Illuminate\Support\Facades\Cache;

class PollService
{
	const CACHE_KEY = 'pf:services:poll:status_id:';

	public static function get($id, $profileId = false)
	{
		$key = self::CACHE_KEY . $id;

		$res = Cache::remember($key, 1800, function() use($id) {
			$poll = Poll::whereStatusId($id)->firstOrFail();
			return [
				'id' => (string) $poll->id,
				'expires_at' => $poll->expires_at->format('c'),
				'expired' => null,
				'multiple' => $poll->multiple,
				'votes_count' => $poll->votes_count,
				'voters_count' => null,
				'voted' => false,
				'own_votes' => [],
				'options' => collect($poll->poll_options)->map(function($option, $key) use($poll) {
					$tally = $poll->cached_tallies && isset($poll->cached_tallies[$key]) ? $poll->cached_tallies[$key] : 0;
					return [
						'title' => $option,
						'votes_count' => $tally
					];
				})->toArray(),
				'emojis' => []
			];
		});

		if($profileId) {
			$res['voted'] = self::voted($id, $profileId);
			$res['own_votes'] = self::ownVotes($id, $profileId);
		}

		return $res;
	}

	public static function getById($id, $pid)
	{
		$poll = Poll::findOrFail($id);
		return self::get($poll->status_id, $pid);
	}

	public static function del($id)
	{
		Cache::forget(self::CACHE_KEY . $id);
	}

	public static function voted($id, $profileId = false)
	{
		return !$profileId ? false : PollVote::whereStatusId($id)
			->whereProfileId($profileId)
			->exists();
	}

	public static function ownVotes($id, $profileId = false)
	{
		return !$profileId ? [] : PollVote::whereStatusId($id)
			->whereProfileId($profileId)
			->pluck('choice') ?? [];
	}
}
