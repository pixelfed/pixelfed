<?php

namespace App\Services;

use Cache;
use App\Util\Lexer\PrettyNumber;
use App\{
	Contact,
	FailedJob,
	Hashtag,
	Instance,
	Media,
	Like,
	Profile,
	Report,
	Status,
	User
};

class AdminStatsService
{
	public static function get()
	{
		return array_merge(self::recentData(), self::additionalData());
	}

	protected static function recentData()
	{
		$day = config('database.default') == 'pgsql' ? 'DATE_PART(\'day\',' : 'day(';
		return Cache::remember('admin:dashboard:home:data:v0:15min', now()->addMinutes(15), function() use ($day) {
			return [
				'contact' => PrettyNumber::convert(Contact::whereNull('read_at')->count()),
				'reports' =>  PrettyNumber::convert(Report::whereNull('admin_seen')->count()),
			];
		});
	}

	protected static function additionalData()
	{
		$day = config('database.default') == 'pgsql' ? 'DATE_PART(\'day\',' : 'day(';
		return Cache::remember('admin:dashboard:home:data:v0:24hr', now()->addHours(24), function() use ($day) {
			return [
				'failedjobs' => PrettyNumber::convert(FailedJob::where('failed_at', '>=', \Carbon\Carbon::now()->subDay())->count()),
				'statuses' => PrettyNumber::convert(Status::whereNull('in_reply_to_id')->whereNull('reblog_of_id')->count()),
				'profiles' => PrettyNumber::convert(Profile::count()),
				'users' => PrettyNumber::convert(User::count()),
				'instances' => PrettyNumber::convert(Instance::count()),
				'media' => PrettyNumber::convert(Media::count()),
				'storage' => Media::sum('size'),
			];
		});
	}

}