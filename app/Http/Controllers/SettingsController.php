<?php

namespace App\Http\Controllers;

use App\AccountLog;

use Auth;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Settings\{
    HomeSettings,
    PrivacySettings,
    SecuritySettings
};

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
}

