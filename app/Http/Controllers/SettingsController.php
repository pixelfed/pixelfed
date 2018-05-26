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
        'name'  => 'required|string|max:30',
      ]);

      $name = $request->input('name');
      $user = Auth::user();
      $profile = $user->profile;

      if($profile->name != $name) {
        $user->name = $name;
        $user->save();

        $profile->name = $name;
        $profile->save();

        return redirect('/settings/home')->with('status', 'Profile successfully updated!');
      }
      return redirect('/settings/home');
    }

    public function password()
    {
      return view('settings.password');
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
