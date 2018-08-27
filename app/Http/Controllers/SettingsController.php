<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{AccountLog, EmailVerification, Media, Profile, User};
use Auth, DB;
use App\Util\Lexer\PrettyNumber;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function home()
    {
      $id = Auth::user()->profile->id;
      $storage = [];
      $used = Media::whereProfileId($id)->sum('size');
      $storage['limit'] = config('pixelfed.max_account_size') * 1024;
      $storage['used'] = $used;
      $storage['percentUsed'] = ceil($storage['used'] / $storage['limit'] * 100);
      $storage['limitPretty'] = PrettyNumber::size($storage['limit']);
      $storage['usedPretty'] = PrettyNumber::size($storage['used']);
      return view('settings.home', compact('storage'));
    }

    public function homeUpdate(Request $request)
    {
      $this->validate($request, [
        'name'  => 'required|string|max:' . config('pixelfed.max_name_length'),
        'bio'   => 'nullable|string|max:' . config('pixelfed.max_bio_length'),
        'website' => 'nullable|url',
        'email' => 'nullable|email'
      ]);

      $changes = false;
      $name = $request->input('name');
      $bio = $request->input('bio');
      $website = $request->input('website');
      $email = $request->input('email');
      $user = Auth::user();
      $profile = $user->profile;

      $validate = config('pixelfed.enforce_email_verification');

      if($user->email != $email) {
        $changes = true;
        $user->email = $email;

        if($validate) {
          $user->email_verified_at = null;
          // Prevent old verifications from working
          EmailVerification::whereUserId($user->id)->delete();
        }
      }

      // Only allow email to be updated if not yet verified
      if(!$validate || !$changes && $user->email_verified_at) {
        if($profile->name != $name) {
          $changes = true;
          $user->name = $name;
          $profile->name = $name;
        }

        if(!$profile->website || $profile->website != $website) {
          $changes = true;
          $profile->website = $website;
        }

        if(!$profile->bio || !$profile->bio != $bio) {
          $changes = true;
          $profile->bio = $bio;
        }
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
          'show_profile_follower_count',
          'show_profile_following_count'
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
