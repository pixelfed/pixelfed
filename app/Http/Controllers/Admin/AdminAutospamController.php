<?php

namespace App\Http\Controllers\Admin;

use App\AccountInterstitial;
use App\Http\Resources\AdminSpamReport;
use App\Jobs\AutospamPipeline\AutospamPretrainNonSpamPipeline;
use App\Jobs\AutospamPipeline\AutospamPretrainPipeline;
use App\Jobs\AutospamPipeline\AutospamUpdateCachedDataPipeline;
use App\Models\AutospamCustomTokens;
use App\Profile;
use App\Services\AccountService;
use App\Services\AutospamService;
use App\Services\ConfigCacheService;
use Cache;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait AdminAutospamController
{
    public function autospamHome(Request $request)
    {
        return view('admin.autospam.home');
    }

    public function getAutospamConfigApi(Request $request)
    {
        $open = Cache::remember('admin-dash:reports:spam-count', 3600, function () {
            return AccountInterstitial::whereType('post.autospam')->whereNull('appeal_handled_at')->count();
        });

        $closed = Cache::remember('admin-dash:reports:spam-count-closed', 3600, function () {
            return AccountInterstitial::whereType('post.autospam')->whereNotNull('appeal_handled_at')->count();
        });

        $thisWeek = Cache::remember('admin-dash:reports:spam-count-stats-this-week ', 86400, function () {
            $sr = config('database.default') == 'pgsql' ? "to_char(created_at, 'MM-YYYY')" : "DATE_FORMAT(created_at, '%m-%Y')";
            $gb = config('database.default') == 'pgsql' ? [DB::raw($sr)] : DB::raw($sr);
            $s = AccountInterstitial::select(
                DB::raw('count(id) as count'),
                DB::raw($sr.' as month_year')
            )
                ->where('created_at', '>=', now()->subWeeks(52))
                ->groupBy($gb)
                ->get()
                ->map(function ($s) {
                    $dt = now()->parse('01-'.$s->month_year);

                    return [
                        'id' => $dt->format('Ym'),
                        'x' => $dt->format('M Y'),
                        'y' => $s->count,
                    ];
                })
                ->sortBy('id')
                ->values()
                ->toArray();

            return $s;
        });

        $files = [
            'spam' => [
                'exists' => Storage::exists(AutospamService::MODEL_SPAM_PATH),
                'size' => 0,
            ],
            'ham' => [
                'exists' => Storage::exists(AutospamService::MODEL_HAM_PATH),
                'size' => 0,
            ],
            'combined' => [
                'exists' => Storage::exists(AutospamService::MODEL_FILE_PATH),
                'size' => 0,
            ],
        ];

        if ($files['spam']['exists']) {
            $files['spam']['size'] = Storage::size(AutospamService::MODEL_SPAM_PATH);
        }

        if ($files['ham']['exists']) {
            $files['ham']['size'] = Storage::size(AutospamService::MODEL_HAM_PATH);
        }

        if ($files['combined']['exists']) {
            $files['combined']['size'] = Storage::size(AutospamService::MODEL_FILE_PATH);
        }

        return [
            'autospam_enabled' => (bool) config_cache('pixelfed.bouncer.enabled') ?? false,
            'nlp_enabled' => (bool) AutospamService::active(),
            'files' => $files,
            'open' => $open,
            'closed' => $closed,
            'graph' => collect($thisWeek)->map(fn ($s) => $s['y'])->values(),
            'graphLabels' => collect($thisWeek)->map(fn ($s) => $s['x'])->values(),
        ];
    }

    public function getAutospamReportsClosedApi(Request $request)
    {
        $appeals = AdminSpamReport::collection(
            AccountInterstitial::orderBy('id', 'desc')
                ->whereType('post.autospam')
                ->whereIsSpam(true)
                ->whereNotNull('appeal_handled_at')
                ->cursorPaginate(6)
                ->withQueryString()
        );

        return $appeals;
    }

    public function postAutospamTrainSpamApi(Request $request)
    {
        $aiCount = AccountInterstitial::whereItemType('App\Status')
            ->whereIsSpam(true)
            ->count();
        abort_if($aiCount < 100, 422, 'You don\'t have enough data to pre-train against.');

        $existing = Cache::get('pf:admin:autospam:pretrain:recent');
        abort_if($existing, 422, 'You\'ve already run this recently, please wait 30 minutes before pre-training again');
        AutospamPretrainPipeline::dispatch();
        Cache::put('pf:admin:autospam:pretrain:recent', 1, 1440);

        return [
            'msg' => 'Success!',
        ];
    }

    public function postAutospamTrainNonSpamSearchApi(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|string|min:1',
        ]);

        $q = $request->input('q');

        $res = Profile::whereNull(['status', 'domain'])
            ->where('username', 'like', '%'.$q.'%')
            ->orderByDesc('followers_count')
            ->take(10)
            ->get()
            ->map(function ($p) {
                $acct = AccountService::get($p->id, true);

                return [
                    'id' => (string) $p->id,
                    'avatar' => $acct['avatar'],
                    'username' => $p->username,
                ];
            })
            ->values();

        return $res;
    }

    public function postAutospamTrainNonSpamSubmitApi(Request $request)
    {
        $this->validate($request, [
            'accounts' => 'required|array|min:1|max:10',
        ]);

        $accts = $request->input('accounts');

        $accounts = Profile::whereNull(['domain', 'status'])->find(collect($accts)->map(function ($a) {
            return $a['id'];
        }));

        abort_if(! $accounts || ! $accounts->count(), 422, 'One or more of the selected accounts are not valid');

        AutospamPretrainNonSpamPipeline::dispatch($accounts);

        return $accounts;
    }

    public function getAutospamCustomTokensApi(Request $request)
    {
        return AutospamCustomTokens::latest()->cursorPaginate(6);
    }

    public function saveNewAutospamCustomTokensApi(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|unique:autospam_custom_tokens,token',
        ]);

        $ct = new AutospamCustomTokens;
        $ct->token = $request->input('token');
        $ct->weight = $request->input('weight');
        $ct->category = $request->input('category') === 'spam' ? 'spam' : 'ham';
        $ct->note = $request->input('note');
        $ct->active = $request->input('active');
        $ct->save();

        AutospamUpdateCachedDataPipeline::dispatch();

        return $ct;
    }

    public function updateAutospamCustomTokensApi(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'token' => 'required',
            'category' => 'required|in:spam,ham',
            'active' => 'required|boolean',
        ]);

        $ct = AutospamCustomTokens::findOrFail($request->input('id'));
        $ct->weight = $request->input('weight');
        $ct->category = $request->input('category');
        $ct->note = $request->input('note');
        $ct->active = $request->input('active');
        $ct->save();

        AutospamUpdateCachedDataPipeline::dispatch();

        return $ct;
    }

    public function exportAutospamCustomTokensApi(Request $request)
    {
        abort_if(! Storage::exists(AutospamService::MODEL_SPAM_PATH), 422, 'Autospam Dataset does not exist, please train spam before attempting to export');

        return Storage::download(AutospamService::MODEL_SPAM_PATH);
    }

    public function enableAutospamApi(Request $request)
    {
        ConfigCacheService::put('autospam.nlp.enabled', true);
        Cache::forget(AutospamService::CHCKD_CACHE_KEY);

        return ['msg' => 'Success'];
    }

    public function disableAutospamApi(Request $request)
    {
        ConfigCacheService::put('autospam.nlp.enabled', false);
        Cache::forget(AutospamService::CHCKD_CACHE_KEY);

        return ['msg' => 'Success'];
    }
}
