<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\AccountInterstitial;
use App\Http\Controllers\Controller;
use App\Instance;
use App\Services\AdminStatsService;
use App\Services\SnowflakeService;
use App\Status;
use App\User;
use Cache;
use DB;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function getStats(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        $res = AdminStatsService::summary();
        $res['autospam_count'] = AccountInterstitial::whereType('post.autospam')
            ->whereNull('appeal_handled_at')
            ->count();

        return $res;
    }

    public function getAllStats(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin === 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        if ($request->has('refresh')) {
            Cache::forget('admin-api:instance-all-stats-v1');
        }

        return Cache::remember('admin-api:instance-all-stats-v1', 1209600, function () {
            $days = range(1, 7);
            $res = [
                'cached_at' => now()->format('c'),
            ];
            $minStatusId = SnowflakeService::byDate(now()->subDays(7));

            foreach ($days as $day) {
                $label = now()->subDays($day)->format('D');
                $labelShort = substr($label, 0, 1);
                $res['users']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => User::whereDate('created_at', now()->subDays($day))->count(),
                ];

                $res['posts']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => Status::whereNull('uri')
                        ->where('id', '>', $minStatusId)
                        ->whereDate('created_at', now()->subDays($day))
                        ->count(),
                ];

                $res['instances']['days'][] = [
                    'date' => now()->subDays($day)->format('M j Y'),
                    'label_full' => $label,
                    'label' => $labelShort,
                    'count' => Instance::whereDate('created_at', now()->subDays($day))->count(),
                ];
            }

            $res['users']['total'] = DB::table('users')->count();
            $res['users']['min'] = collect($res['users']['days'])->min('count');
            $res['users']['max'] = collect($res['users']['days'])->max('count');
            $res['users']['change'] = collect($res['users']['days'])->sum('count');
            $res['posts']['total'] = DB::table('statuses')->whereNull('uri')->count();
            $res['posts']['min'] = collect($res['posts']['days'])->min('count');
            $res['posts']['max'] = collect($res['posts']['days'])->max('count');
            $res['posts']['change'] = collect($res['posts']['days'])->sum('count');
            $res['instances']['total'] = DB::table('instances')->count();
            $res['instances']['min'] = collect($res['instances']['days'])->min('count');
            $res['instances']['max'] = collect($res['instances']['days'])->max('count');
            $res['instances']['change'] = collect($res['instances']['days'])->sum('count');

            return $res;
        });
    }
}