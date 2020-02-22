<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\Following;
use App\Report;
use App\Status;
use App\UserFilter;
use Auth;
use Cookie;
use DB;
use Cache;
use Purify;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Transformer\ActivityPub\ProfileTransformer;
use App\Transformer\ActivityPub\StatusTransformer;
use App\Transformer\Api\StatusTransformer as StatusApiTransformer;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

trait ExportSettings
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dataExport()
    {
        return view('settings.dataexport');
    }

    public function exportAccount()
    {
        $data = Cache::remember('account:export:profile:actor:'.Auth::user()->profile->id, now()->addMinutes(60), function () {
            $profile = Auth::user()->profile;
            $fractal = new Fractal\Manager();
            $fractal->setSerializer(new ArraySerializer());
            $resource = new Fractal\Resource\Item($profile, new ProfileTransformer());
            return $fractal->createData($resource)->toArray();
        });

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        }, 'account.json', [
            'Content-Type' => 'application/json'
        ]);
    }

    public function exportFollowing()
    {
        $data = Cache::remember('account:export:profile:following:'.Auth::user()->profile->id, now()->addMinutes(60), function () {
            return Auth::user()->profile->following()->get()->map(function ($i) {
                return $i->url();
            });
        });
        return response()->streamDownload(function () use ($data) {
            echo $data;
        }, 'following.json', [
            'Content-Type' => 'application/json'
        ]);
    }

    public function exportFollowers()
    {
        $data = Cache::remember('account:export:profile:followers:'.Auth::user()->profile->id, now()->addMinutes(60), function () {
            return Auth::user()->profile->followers()->get()->map(function ($i) {
                return $i->url();
            });
        });
        return response()->streamDownload(function () use ($data) {
            echo $data;
        }, 'followers.json', [
            'Content-Type' => 'application/json'
        ]);
    }

    public function exportMuteBlockList()
    {
        $profile = Auth::user()->profile;
        $exists = UserFilter::select('id')
            ->whereUserId($profile->id)
            ->exists();
        if (!$exists) {
            return redirect()->back();
        }
        $data = Cache::remember('account:export:profile:muteblocklist:'.Auth::user()->profile->id, now()->addMinutes(60), function () use ($profile) {
            return json_encode([
                'muted' => $profile->mutedProfileUrls(),
                'blocked' => $profile->blockedProfileUrls()
            ], JSON_PRETTY_PRINT);
        });
        return response()->streamDownload(function () use ($data) {
            echo $data;
        }, 'muted-and-blocked-accounts.json', [
            'Content-Type' => 'application/json'
        ]);
    }

    public function exportStatuses(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|string|in:ap,api'
        ]);
        $limit = 500;

        $profile = Auth::user()->profile;
        $type = 'ap';

        $count = Status::select('id')->whereProfileId($profile->id)->count();
        if ($count > $limit) {
            // fire background job
            return redirect('/settings/data-export')->with(['status' => 'You have more than '.$limit.' statuses, we do not support full account export yet.']);
        }

        $filename = 'outbox.json';
        if ($type == 'ap') {
            $data = Cache::remember('account:export:profile:statuses:ap:'.Auth::user()->profile->id, now()->addHours(1), function () {
                $profile = Auth::user()->profile->statuses;
                $fractal = new Fractal\Manager();
                $fractal->setSerializer(new ArraySerializer());
                $resource = new Fractal\Resource\Collection($profile, new StatusTransformer());
                return $fractal->createData($resource)->toArray();
            });
        } else {
            $filename = 'api-statuses.json';
            $data = Cache::remember('account:export:profile:statuses:api:'.Auth::user()->profile->id, now()->addHours(1), function () {
                $profile = Auth::user()->profile->statuses;
                $fractal = new Fractal\Manager();
                $fractal->setSerializer(new ArraySerializer());
                $resource = new Fractal\Resource\Collection($profile, new StatusApiTransformer());
                return $fractal->createData($resource)->toArray();
            });
        }

        return response()->streamDownload(function () use ($data, $filename) {
            echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json'
        ]);
    }
}
