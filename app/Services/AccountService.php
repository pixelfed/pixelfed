<?php

namespace App\Services;

use Cache;
use App\Profile;
use App\Status;
use App\Transformer\Api\AccountTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountService
{
	const CACHE_KEY = 'pf:services:account:';

	public static function get($id)
	{
		if($id > PHP_INT_MAX || $id < 1) {
			return [];
		}

		$key = self::CACHE_KEY . $id;
		$ttl = now()->addHours(12);

		return Cache::remember($key, $ttl, function() use($id) {
			$fractal = new Fractal\Manager();
			$fractal->setSerializer(new ArraySerializer());
			$profile = Profile::findOrFail($id);
			$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
			return $fractal->createData($resource)->toArray();
		});	
	}

	public static function del($id)
	{
		return Cache::forget(self::CACHE_KEY . $id);
	}

	public static function syncPostCount($id)
	{
		$profile = Profile::find($id);

		if(!$profile) {
			return false;
		}

		$key = self::CACHE_KEY . 'pcs:' . $id;

		if(Cache::has($key)) {
			return;
		}

		$count = Status::whereProfileId($id)
			->whereNull('in_reply_to_id')
			->whereNull('reblog_of_id')
			->whereIn('scope', ['public', 'unlisted', 'private'])
			->count();

		$profile->status_count = $count;
		$profile->save();

		Cache::put($key, 1, 900);
		return true;
	}

	public static function usernameToId($username)
	{
		$key = self::CACHE_KEY . 'u2id:' . hash('sha256', $username);
		return Cache::remember($key, 900, function() use($username) {
			$s = Str::of($username);
			if($s->contains('@') && !$s->startsWith('@')) {
				$username = "@{$username}";
			}
			$profile = DB::table('profiles')
				->whereUsername($username)
				->first();
			if(!$profile) {
				return null;
			}
			return (string) $profile->id;
		});
	}
}
