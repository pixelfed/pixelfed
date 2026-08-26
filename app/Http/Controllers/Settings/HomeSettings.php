<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\EmailVerification;
use App\Mail\PasswordChange;
use App\Media;
use App\Services\AccountService;
use App\Services\LandingCacheService;
use App\Services\PronounService;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\PrettyNumber;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Purify;

trait HomeSettings
{
    public function home()
    {
        $id = Auth::user()->profile_id;
        $storage = [];
        $used = Media::whereProfileId($id)->sum('size');
        $storage['limit'] = config_cache('pixelfed.max_account_size') * 1024;
        $storage['used'] = $used;
        $storage['percentUsed'] = ceil($storage['used'] / $storage['limit'] * 100);
        $storage['limitPretty'] = PrettyNumber::size($storage['limit']);
        $storage['usedPretty'] = PrettyNumber::size($storage['used']);
        $pronouns = PronounService::get($id);

        return view('settings.home', compact('storage', 'pronouns'));
    }

    public function homeUpdate(Request $request)
    {
        $this->validate($request, [
            'name' => 'nullable|string|max:'.config('pixelfed.max_name_length'),
            'bio' => 'nullable|string|max:'.config('pixelfed.max_bio_length'),
            'website' => 'nullable|url',
            'language' => 'nullable|string|min:2|max:5',
            'pronouns' => 'nullable|array|max:4',
        ]);

        $changes = false;
        $name = strip_tags(Purify::clean($request->input('name')));
        $bio = $request->filled('bio') ? strip_tags(Purify::clean($request->input('bio'))) : null;
        $website = $request->input('website');
        $language = $request->input('language');
        $user = Auth::user();
        $profile = $user->profile;
        $pronouns = $request->input('pronouns');
        $existingPronouns = PronounService::get($profile->id);
        $layout = $request->input('profile_layout');
        if ($layout) {
            $layout = ! in_array($layout, ['metro', 'moment']) ? 'metro' : $layout;
        }

        $enforceEmailVerification = config_cache('pixelfed.enforce_email_verification');

        // Only allow email to be updated if not yet verified
        if (! $enforceEmailVerification || $user->email_verified_at) {
            if ($profile->name != $name) {
                $changes = true;
                $user->name = $name;
                $profile->name = $name;
            }

            if ($profile->website != $website) {
                $changes = true;
                $profile->website = $website;
            }

            if (strip_tags($profile->bio) != $bio) {
                $changes = true;
                $profile->bio = Autolink::create()->autolink($bio);
            }

            if ($user->language != $language &&
                in_array($language, \App\Util\Localization\Localization::languages())
            ) {
                $changes = true;
                $user->language = $language;
                session()->put('locale', $language);
            }

            if ($existingPronouns != $pronouns) {
                if ($pronouns && in_array('Select Pronoun(s)', $pronouns)) {
                    PronounService::clear($profile->id);
                } else {
                    PronounService::put($profile->id, $pronouns);
                }
            }
        } else {
            return redirect('/settings/home')->with('status', 'Verify your email address before you can update your profile!');
        }

        if ($changes === true) {
            $user->save();
            $profile->save();
            Cache::forget('user:account:id:'.$user->id);
            AccountService::forgetAccountSettings($profile->id);
            AccountService::del($profile->id);
            if (LandingCacheService::profileBacksContact((int) $profile->id)) {
                LandingCacheService::invalidateContact();
            }

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
            'current' => 'required|string',
            'password' => 'required|string|confirmed|min:8|different:current',
            'revoke_sessions' => 'nullable|boolean',
        ]);

        $current = $request->input('current');
        $new = $request->input('password');
        $revokeSessions = $request->boolean('revoke_sessions');

        $user = $request->user();

        if (!password_verify($current, $user->password)) {
            return redirect()->back()->with('error', 'There was an error with your request! Please try again.');
        }

        $user->password = bcrypt($new);
        $user->save();

        $log = new AccountLog;
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = 'App\User';
        $log->action = 'account.edit.password';
        $log->message = $revokeSessions
            ? 'Password changed and all sessions revoked'
            : 'Password changed';
        $log->link = null;
        $log->ip_address = $request->ip();
        $log->user_agent = $request->userAgent();
        $log->save();

        Mail::to($request->user())->send(new PasswordChange($user));

        if ($revokeSessions) {
            $user->tokens->each(function ($token) {
                $token->revoke();
                $token->refreshToken?->revoke();
            });

            Auth::logoutOtherDevices($new);
        }

        return redirect('/settings/home')->with('status', 'Password successfully updated!');
    }

    public function email()
    {
        return view('settings.email');
    }

    public function emailUpdate(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users,email',
        ]);
        $changes = false;
        $email = $request->input('email');
        $user = Auth::user();
        $profile = $user->profile;

        $validate = config_cache('pixelfed.enforce_email_verification');

        if ($user->email != $email) {
            $changes = true;
            $user->email = $email;

            if ($validate) {
                // auto verify admin email addresses
                $user->email_verified_at = $user->is_admin == true ? now() : null;
                // Prevent old verifications from working
                EmailVerification::whereUserId($user->id)->delete();
            }

            $log = new AccountLog;
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

        if ($changes === true) {
            Cache::forget('user:account:id:'.$user->id);
            $user->save();
            $profile->save();

            return redirect('/settings/email')->with('status', 'Email successfully updated!');
        } else {
            return redirect('/settings/email');
        }

    }

    public function avatar()
    {
        return view('settings.avatar');
    }
}
