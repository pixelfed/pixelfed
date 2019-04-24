<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\Following;
use App\Report;
use App\UserFilter;
use Auth, Cookie, DB, Cache, Purify;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait ExportSettings
{

    public function dataExport()
    {
        return view('settings.dataexport');
    }

    public function exportFollowing()
    {
        $data = Cache::remember('account:export:profile:following:'.Auth::user()->profile->id, now()->addMinutes(60), function() {
            return Auth::user()->profile->following()->get()->map(function($i) {
                return $i->url();
            });
        });
        return response()->streamDownload(function () use($data) {
            echo $data;
        }, 'following.json');
    }

    public function exportFollowers()
    {
        $data = Cache::remember('account:export:profile:followers:'.Auth::user()->profile->id, now()->addMinutes(60), function() {
            return Auth::user()->profile->followers()->get()->map(function($i) {
                return $i->url();
            });
        });
        return response()->streamDownload(function () use($data) {
            echo $data;
        }, 'followers.json');
    }

    public function exportMuteBlockList()
    {
        $profile = Auth::user()->profile;
        $exists = UserFilter::select('id')
            ->whereUserId($profile->id)
            ->exists();
        if(!$exists) {
            return redirect()->back();
        }
        $data = Cache::remember('account:export:profile:muteblocklist:'.Auth::user()->profile->id, now()->addMinutes(60), function() use($profile) {
            return json_encode([
                'muted' => $profile->mutedProfileUrls(),
                'blocked' => $profile->blockedProfileUrls()
            ], JSON_PRETTY_PRINT);
        });
        return response()->streamDownload(function () use($data) {
            echo $data;
        }, 'muted-and-blocked-accounts.json');
    }

}