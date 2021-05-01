<?php

namespace App\Http\Controllers;

use App\AccountLog;
use App\Following;
use App\ProfileSponsor;
use App\Report;
use App\UserFilter;
use Auth, Cookie, DB, Cache, Purify;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Settings\{
	ExportSettings,
	LabsSettings,
	HomeSettings,
	PrivacySettings,
	RelationshipSettings,
	SecuritySettings
};
use App\Jobs\DeletePipeline\DeleteAccountPipeline;

class SettingsController extends Controller
{
	use ExportSettings,
	LabsSettings,
	HomeSettings,
	PrivacySettings,
	RelationshipSettings,
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

	public function dataImport()
	{
		abort_if(!config('pixelfed.import.instagram.enabled'), 404);
		return view('settings.import.home');
	}

	public function dataImportInstagram()
	{
		abort_if(!config('pixelfed.import.instagram.enabled'), 404);
		return view('settings.import.instagram.home');
	}

	public function developers()
	{
		return view('settings.developers');
	}

	public function removeAccountTemporary(Request $request)
	{
		$user = Auth::user();
		abort_if(!config('pixelfed.account_deletion'), 403);
		abort_if($user->is_admin, 403);

		return view('settings.remove.temporary');
	}

	public function removeAccountTemporarySubmit(Request $request)
	{
		$user = Auth::user();
		abort_if(!config('pixelfed.account_deletion'), 403);
		abort_if($user->is_admin, 403);
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
		$user = Auth::user();
		abort_if($user->is_admin, 403);
		return view('settings.remove.permanent');
	}

	public function removeAccountPermanentSubmit(Request $request)
	{
		if(config('pixelfed.account_deletion') == false) {
			abort(404);
		}
		$user = Auth::user();
		abort_if(!config('pixelfed.account_deletion'), 403);
		abort_if($user->is_admin, 403);
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
		DeleteAccountPipeline::dispatch($user)->onQueue('high');
		return redirect('/');
	}

	public function requestFullExport(Request $request)
	{
		$user = Auth::user();
		return view('settings.export.show');
	}

	public function metroDarkMode(Request $request)
	{
		$this->validate($request, [
			'mode' => 'required|string|in:light,dark'
		]);

		$mode = $request->input('mode');

		if($mode == 'dark') {
			$cookie = Cookie::make('dark-mode', true, 43800);
		} else {
			$cookie = Cookie::forget('dark-mode');
		}

		return response()->json([200])->cookie($cookie);
	}

	public function sponsor()
	{
		$default = [
			'patreon' => null,
			'liberapay' => null,
			'opencollective' => null
		];
		$sponsors = ProfileSponsor::whereProfileId(Auth::user()->profile->id)->first();
		$sponsors = $sponsors ? json_decode($sponsors->sponsors, true) : $default;
		return view('settings.sponsor', compact('sponsors'));
	}

	public function sponsorStore(Request $request)
	{
		$this->validate($request, [
			'patreon' => 'nullable|string',
			'liberapay' => 'nullable|string',
			'opencollective' => 'nullable|string'
		]);

		$patreon = Str::startsWith($request->input('patreon'), 'https://') ?
			substr($request->input('patreon'), 8) :
			$request->input('patreon');

		$liberapay = Str::startsWith($request->input('liberapay'), 'https://') ?
			substr($request->input('liberapay'), 8) :
			$request->input('liberapay');

		$opencollective = Str::startsWith($request->input('opencollective'), 'https://') ?
			substr($request->input('opencollective'), 8) :
			$request->input('opencollective');

		$patreon = Str::startsWith($patreon, 'patreon.com/') ? e($patreon) : null;
		$liberapay = Str::startsWith($liberapay, 'liberapay.com/') ? e($liberapay) : null;
		$opencollective = Str::startsWith($opencollective, 'opencollective.com/') ? e($opencollective) : null;

		if(empty($patreon) && empty($liberapay) && empty($opencollective)) {
			return redirect(route('settings'))->with('error', 'An error occured. Please try again later.');;
		}

		$res = [
			'patreon' => $patreon,
			'liberapay' => $liberapay,
			'opencollective' => $opencollective
		];

		$sponsors = ProfileSponsor::firstOrCreate([
			'profile_id' => Auth::user()->profile_id ?? Auth::user()->profile->id
		]);
		$sponsors->sponsors = json_encode($res);
		$sponsors->save();
		$sponsors = $res;
		return redirect(route('settings'))->with('status', 'Sponsor settings successfully updated!');;
	}

}

