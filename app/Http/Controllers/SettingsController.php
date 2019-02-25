<?php

namespace App\Http\Controllers;

use App\AccountLog;
use App\Following;
use App\Report;
use App\UserFilter;
use Auth, DB, Cache, Purify;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Settings\{
    HomeSettings,
    PrivacySettings,
    SecuritySettings
};
use App\Jobs\DeletePipeline\DeleteAccountPipeline;

class SettingsController extends Controller
{
    use HomeSettings,
    PrivacySettings,
    SecuritySettings;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function accessibility()
    {
        $settings = Auth::user()->settings;

        return view('settings.accessibility', compact('settings'));
    }

    public function accessibilityStore(Request $request)
    {
        $settings = Auth::user()->settings;
        $fields = [
          'compose_media_descriptions',
          'reduce_motion',
          'optimize_screen_reader',
          'high_contrast_mode',
          'video_autoplay',
        ];
        foreach ($fields as $field) {
            $form = $request->input($field);
            if ($form == 'on') {
                $settings->{$field} = true;
            } else {
                $settings->{$field} = false;
            }
            $settings->save();
        }

        return redirect(route('settings.accessibility'))->with('status', 'Settings successfully updated!');
    }

    public function notifications()
    {
        return view('settings.notifications');
    }

    public function applications()
    {
        return view('settings.applications');
    }

    public function dataExport()
    {
        return view('settings.dataexport');
    }

    public function exportFollowing()
    {
        $data = Cache::remember('account:export:profile:following:'.Auth::user()->profile->id, now()->addMinutes(1440), function() {
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
        $data = Cache::remember('account:export:profile:followers:'.Auth::user()->profile->id, now()->addMinutes(1440), function() {
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
        $data = Cache::remember('account:export:profile:muteblocklist:'.Auth::user()->profile->id, now()->addMinutes(1440), function() use($profile) {
            return json_encode([
                'muted' => $profile->mutedProfileUrls(),
                'blocked' => $profile->blockedProfileUrls()
            ], JSON_PRETTY_PRINT);
        });
        return response()->streamDownload(function () use($data) {
            echo $data;
        }, 'muted-and-blocked-accounts.json');
    }

    public function dataImport()
    {
        return view('settings.import.home');
    }

    public function dataImportInstagram()
    {
        return view('settings.import.instagram.home');
    }

    public function developers()
    {
        return view('settings.developers');
    }

    public function removeAccountTemporary(Request $request)
    {
        return view('settings.remove.temporary');
    }

    public function removeAccountTemporarySubmit(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile;
        $user->status = 'disabled';
        $profile->status = 'disabled';
        $user->save();
        $profile->save();
        Auth::logout();
        Cache::forget('profiles:private');
        return redirect('/');
    }

    public function removeAccountPermanent(Request $request)
    {
        if(config('pixelfed.account_deletion') == false) {
            abort(404);
        }
        return view('settings.remove.permanent');
    }

    public function removeAccountPermanentSubmit(Request $request)
    {
        if(config('pixelfed.account_deletion') == false) {
            abort(404);
        }
        $user = Auth::user();
        if($user->is_admin == true) {
            return abort(400, 'You cannot delete an admin account.');
        }
        $profile = $user->profile;
        $ts = Carbon::now()->addMonth();
        $user->status = 'delete';
        $profile->status = 'delete';
        $user->delete_after = $ts;
        $profile->delete_after = $ts;
        $user->save();
        $profile->save();
        Cache::forget('profiles:private');
        Auth::logout();
        return redirect('/');
    }

    public function requestFullExport(Request $request)
    {
        $user = Auth::user();
        return view('settings.export.show');
    }

    public function reportsHome(Request $request)
    {
        $profile = Auth::user()->profile;
        $reports = Report::whereProfileId($profile->id)->orderByDesc('created_at')->paginate(10);
        return view('settings.reports', compact('reports'));
    }
}

