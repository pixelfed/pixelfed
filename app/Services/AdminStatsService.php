<?php

namespace App\Services;

use Cache;
use DB;
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
use \DateInterval;
use \DatePeriod;

class AdminStatsService
{
	public static function get()
	{
		return array_merge(
				self::recentData(),
				self::additionalData(),
				self::postsGraph()
			);
	}

	protected static function recentData()
	{
		$day = config('database.default') == 'pgsql' ? 'DATE_PART(\'day\',' : 'day(';
		$ttl = now()->addMinutes(15);
		return Cache::remember('admin:dashboard:home:data:v0:15min', $ttl, function() use ($day) {
			return [
				'contact' => PrettyNumber::convert(Contact::whereNull('read_at')->count()),
				'contact_monthly' => PrettyNumber::convert(Contact::whereNull('read_at')->where('created_at', '>', now()->subMonth())->count()),
				'reports' =>  PrettyNumber::convert(Report::whereNull('admin_seen')->count()),
				'reports_monthly' =>  PrettyNumber::convert(Report::whereNull('admin_seen')->where('created_at', '>', now()->subMonth())->count()),
			];
		});
	}

	protected static function additionalData()
	{
		$day = config('database.default') == 'pgsql' ? 'DATE_PART(\'day\',' : 'day(';
		$ttl = now()->addHours(24);
		return Cache::remember('admin:dashboard:home:data:v0:24hr', $ttl, function() use ($day) {
			return [
				'failedjobs' => PrettyNumber::convert(FailedJob::where('failed_at', '>=', \Carbon\Carbon::now()->subDay())->count()),
				'statuses' => PrettyNumber::convert(Status::count()),
				'statuses_monthly' => PrettyNumber::convert(Status::where('created_at', '>', now()->subMonth())->count()),
				'profiles' => PrettyNumber::convert(Profile::count()),
				'users' => PrettyNumber::convert(User::count()),
				'users_monthly' => PrettyNumber::convert(User::where('created_at', '>', now()->subMonth())->count()),
				'instances' => PrettyNumber::convert(Instance::count()),
				'media' => PrettyNumber::convert(Media::count()),
				'storage' => Media::sum('size'),
			];
		});
	}

	protected static function postsGraph()
	{
		$ttl = now()->addHours(12);
		return Cache::remember('admin:dashboard:home:data-postsGraph:v0:24hr', $ttl, function() {
			$s = Status::selectRaw('Date(created_at) as date, count(statuses.id) as count, statuses.*')
				->where('created_at', '>=', now()->subWeek())
				->groupBy(DB::raw('Date(created_at)'))
				->orderBy('created_at', 'DESC')
				->pluck('count', 'date');

			$begin = now()->subWeek();
			$end = now();
			$interval = new DateInterval('P1D');
			$daterange = new DatePeriod($begin, $interval ,$end);
			$dates = [];
			foreach($daterange as $date){
				$dates[$date->format("Y-m-d")] = 0;
			}

			$dates = collect($dates)->merge($s);

			$s = Status::selectRaw('Date(created_at) as date, count(statuses.id) as count, statuses.*')
				->where('created_at', '>=', now()->subWeeks(2))
				->where('created_at', '<=', now()->subWeeks(1))
				->groupBy(DB::raw('Date(created_at)'))
				->orderBy('created_at', 'DESC')
				->pluck('count', 'date');

			$begin = now()->subWeeks(2);
			$end = now()->subWeeks(1);
			$interval = new DateInterval('P1D');
			$daterange = new DatePeriod($begin, $interval ,$end);
			$lw = [];
			foreach($daterange as $date){
				$lw[$date->format("Y-m-d")] = 0;
			}

			$lw = collect($lw)->merge($s);

			return [
				'posts_this_week' => $dates->values(),
				'posts_last_week' => $lw->values(),
			];
		});
	}

}
