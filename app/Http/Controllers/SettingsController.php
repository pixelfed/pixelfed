<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{AccountLog, Profile, User};
use Auth, DB;

class SettingsController extends Controller
{
    public function __construct()
    {
      return $this->middleware('auth');
    }

    public function home()
    {
      return view('settings.home');
    }

    public function homeUpdate(Request $request)
    {
      $this->validate($request, [
        'name'  => 'required|string|max:30',
        'bio'   => 'nullable|string|max:125'
      ]);

      $changes = false;
      $name = $request->input('name');
      $bio = $request->input('bio');
      $user = Auth::user();
      $profile = $user->profile;

      if($profile->name != $name) {
        $changes = true;
        $user->name = $name;
        $profile->name = $name;
      }

      if($profile->bio != $bio) {
        $changes = true;
        $profile->bio = $bio;
      }

      if($changes === true) {
        $user->save();
        $profile->save();
        return redirect('/settings/home')->with('status', 'Profile successfully updated!');
      }

      return redirect('/settings/home');
    }

    public function password()
    {
      return view('settings.password');
    }

    public function passwordUpdate(Request $request)
    {
      $this->validate($request, [
        'current'  => 'required|string',
        'password'  => 'required|string',
        'password_confirmation'  => 'required|string',
      ]);

      $current = $request->input('current');
      $new = $request->input('password');
      $confirm = $request->input('password_confirmation');

      $user = Auth::user();

      if(password_verify($current, $user->password) && $new === $confirm) {
        $user->password = bcrypt($new);
        $user->save();

        return redirect('/settings/home')->with('status', 'Password successfully updated!');
      }
      return redirect('/settings/home')->with('error', 'There was an error with your request!');
    }

    public function email()
    {
      return view('settings.email');
    }

    public function avatar()
    {
      return view('settings.avatar');
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
          'video_autoplay'
      ];
      foreach($fields as $field) {
          $form = $request->input($field);
          if($form == 'on') {
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

    public function privacy()
    {
      $settings = Auth::user()->settings;
      $is_private = Auth::user()->profile->is_private;
      $settings['is_private'] = (bool) $is_private;
      return view('settings.privacy', compact('settings'));
    }

    public function privacyStore(Request $request)
    {
      $settings = Auth::user()->settings;
      $profile = Auth::user()->profile;
      $fields = [
          'is_private',
          'crawlable',
      ];
      foreach($fields as $field) {
          $form = $request->input($field);
          if($field == 'is_private') {
            if($form == 'on') {
               $profile->{$field} = true;
               $settings->show_guests = false;
               $settings->show_discover = false;
               $profile->save();
            } else {
               $profile->{$field} = false;
               $profile->save();
            }
          } elseif($field == 'crawlable') {
            if($form == 'on') {
               $settings->{$field} = false;
            } else {
               $settings->{$field} = true;
            }
          } else {
            if($form == 'on') {
               $settings->{$field} = true;
            } else {
               $settings->{$field} = false;
            }
          }
          $settings->save();
      }
      return redirect(route('settings.privacy'))->with('status', 'Settings successfully updated!');
    }

    public function security()
    {
      $sessions = DB::table('sessions')
        ->whereUserId(Auth::id())
        ->limit(20)
        ->get();
      $activity = AccountLog::whereUserId(Auth::id())
      ->orderBy('created_at','desc')
      ->limit(50)
      ->get();
      return view('settings.security', compact('sessions', 'activity'));
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
