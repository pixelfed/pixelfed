<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\EmailVerification;
use App\Media;
use App\Profile;
use App\User;
use App\UserFilter;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\PrettyNumber;
use Auth;
use Cache;
use DB;
use Mail;
use Purify;
use App\Mail\PasswordChange;
use Illuminate\Http\Request;
use App\Services\PronounService;

trait HomeSettings
{

	public function home()
	{
		$id = Auth::user()->profile->id;
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
			'name'    => 'required|string|max:'.config('pixelfed.max_name_length'),
			'bio'     => 'nullable|string|max:'.config('pixelfed.max_bio_length'),
			'website' => 'nullable|url',
			'language' => 'nullable|string|min:2|max:5',
			'pronouns' => 'nullable|array|max:4'
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
		if($layout) {
			$layout = !in_array($layout, ['metro', 'moment']) ? 'metro' : $layout;
		}

		$enforceEmailVerification = config_cache('pixelfed.enforce_email_verification');

		// Only allow email to be updated if not yet verified
		if (!$enforceEmailVerification || !$changes && $user->email_verified_at) {
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

			if($user->language != $language &&
				in_array($language, \App\Util\Localization\Localization::languages())
			) {
				$changes = true;
				$user->language = $language;
				session()->put('locale', $language);
			}

			if($existingPronouns != $pronouns) {
				if($pronouns && in_array('Select Pronoun(s)', $pronouns)) {
					PronounService::clear($profile->id);
				} else {
					PronounService::put($profile->id, $pronouns);
				}
			}
		}

		if ($changes === true) {
			Cache::forget('user:account:id:'.$user->id);
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

			Mail::to($request->user())->send(new PasswordChange($user));
			return redirect('/settings/home')->with('status', 'Password successfully updated!');
		} else {
			return redirect()->back()->with('error', 'There was an error with your request! Please try again.');
		}

	}

	public function email()
	{
		return view('settings.email');
	}

	public function emailUpdate(Request $request)
	{
		$this->validate($request, [
			'email'   => 'required|email|unique:users,email',
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

		if ($changes === true) {
			Cache::forget('user:account:id:'.$user->id);
			$user->save();
			$profile->save();

			return redirect('/settings/home')->with('status', 'Email successfully updated!');
		} else {
			return redirect('/settings/email');
		}

	}

	public function avatar()
	{
		return view('settings.avatar');
	}

}
