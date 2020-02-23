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
		return Cache::remember('admin:dashboard:home:data:15min', now()->addMinutes(15), function() use ($day) {
			return [
				'contact' => [
					'count' => PrettyNumber::convert(Contact::whereNull('read_at')->count()),
					'graph' => Contact::selectRaw('count(*) as count, '.$day.'created_at) as d')->groupBy('d')->whereNull('read_at')->whereBetween('created_at', [now()->subDays(14), now()])->orderBy('d')->pluck('count')
				],
				'failedjobs' => [
					'count' => PrettyNumber::convert(FailedJob::where('failed_at', '>=', \Carbon\Carbon::now()->subDay())->count()),
					'graph' => FailedJob::selectRaw('count(*) as count, '.$day.'failed_at) as d')->groupBy('d')->whereBetween('failed_at', [now()->subDays(14), now()])->orderBy('d')->pluck('count')
				],
				'reports' => [
					'count' => PrettyNumber::convert(Report::whereNull('admin_seen')->count()),
					'graph' => Report::selectRaw('count(*) as count, '.$day.'created_at) as d')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('d')->orderBy('d')->pluck('count')
				],
				'statuses' => [
					'count' => PrettyNumber::convert(Status::whereNull('in_reply_to_id')->whereNull('reblog_of_id')->count()),
					'graph' => Status::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
				],
				'replies' => [
					'count' => PrettyNumber::convert(Status::whereNotNull('in_reply_to_id')->count()),
					'graph' => Status::whereNotNull('in_reply_to_id')->selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
				],
				'shares' => [
					'count' => PrettyNumber::convert(Status::whereNotNull('reblog_of_id')->count()),
					'graph' => Status::whereNotNull('reblog_of_id')->selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
				],
				'likes' => [
					'count' => PrettyNumber::convert(Like::count()),
					'graph' => Like::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
				],
				'profiles' => [
					'count' => PrettyNumber::convert(Profile::count()),
					'graph' => Profile::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
				],
			];
		});
	}

	protected static function additionalData()
	{
		$day = config('database.default') == 'pgsql' ? 'DATE_PART(\'day\',' : 'day(';
		return Cache::remember('admin:dashboard:home:data:24hr', now()->addHours(24), function() use ($day) {
			return [
				'users' => [
					'count' => PrettyNumber::convert(User::count()),
					'graph' => User::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
				],
				'instances' => [
					'count' => PrettyNumber::convert(Instance::count()),
					'graph' => Instance::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(28), now()])->groupBy('day')->orderBy('day')->pluck('count')
				],
				'media' => [
					'count' => PrettyNumber::convert(Media::count()),
					'graph' => Media::selectRaw('count(*) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
				],
				'storage' => [
					'count' => Media::sum('size'),
					'graph' => Media::selectRaw('sum(size) as count, '.$day.'created_at) as day')->whereBetween('created_at', [now()->subDays(14), now()])->groupBy('day')->orderBy('day')->pluck('count')
				]
			];
		});
	}

}