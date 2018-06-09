<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Profile, User};
use Auth;

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
        'name'  => 'required|string|max:' . config('pixelfed.max_name_length'),
        'bio'   => 'nullable|string|max:' . config('pixelfed.max_bio_length')
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

    public function notifications()
    {
      return view('settings.notifications');
    }

    public function privacy()
    {
      return view('settings.privacy');
    }

    public function security()
    {
      return view('settings.security');
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
      return view('settings.import.ig');
    }

    public function developers()
    {
      return view('settings.developers');
    }
}
