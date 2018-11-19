<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\EmailVerification;
use App\Media;
use App\Profile;
use App\User;
use App\UserFilter;
use App\Util\Lexer\PrettyNumber;
use Auth;
use DB;
use Purify;
use Illuminate\Http\Request;

trait HomeSettings
{

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
        'name'    => 'required|string|max:'.config('pixelfed.max_name_length'),
        'bio'     => 'nullable|string|max:'.config('pixelfed.max_bio_length'),
        'website' => 'nullable|url',
        'email'   => 'nullable|email',
      ]);

        $changes = false;
        $name = strip_tags($request->input('name'));
        $bio = $request->filled('bio') ? Purify::clean($request->input('bio')) : null;
        $website = $request->input('website');
        $email = $request->input('email');
        $user = Auth::user();
        $profile = $user->profile;

        $validate = config('pixelfed.enforce_email_verification');

        if ($user->email != $email) {
            $changes = true;
            $user->email = $email;

            if ($validate) {
                $user->email_verified_at = null;
                // Prevent old verifications from working
                EmailVerification::whereUserId($user->id)->delete();
            }

            $log = new AccountLog();
            $log->user_id = $user->id;
            $log->item_id = $user->id;
            $log->item_type = 'App\User';
            $log->action = 'account.edit.email';
            $log->message = 'Email changed';
            $log->link = null;
            $log->ip_address = $request->ip();
            $log->user_agent = $request->userAgent();
            $log->save();
        }

        // Only allow email to be updated if not yet verified
        if (!$validate || !$changes && $user->email_verified_at) {
            if ($profile->name != $name) {
                $changes = true;
                $user->name = $name;
                $profile->name = $name;
            }

            if ($profile->website != $website) {
                $changes = true;
                $profile->website = $website;
            }

            if ($profile->bio != $bio) {
                $changes = true;
                $profile->bio = $bio;
            }
        }

        if ($changes === true) {
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
        'current'                => 'required|string',
        'password'               => 'required|string',
        'password_confirmation'  => 'required|string',
      ]);

        $current = $request->input('current');
        $new = $request->input('password');
        $confirm = $request->input('password_confirmation');

        $user = Auth::user();

        if (password_verify($current, $user->password) && $new === $confirm) {
            $user->password = bcrypt($new);
            $user->save();

            $log = new AccountLog();
            $log->user_id = $user->id;
            $log->item_id = $user->id;
            $log->item_type = 'App\User';
            $log->action = 'account.edit.password';
            $log->message = 'Password changed';
            $log->link = null;
            $log->ip_address = $request->ip();
            $log->user_agent = $request->userAgent();
            $log->save();

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

}