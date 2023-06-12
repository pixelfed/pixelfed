<?php

namespace App\Http\Controllers;

use App\AccountLog;
use App\Following;
use App\ProfileSponsor;
use App\Report;
use App\UserFilter;
use App\UserSetting;
use Auth, Cookie, DB, Cache, Purify;
use Illuminate\Support\Facades\Redis;
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
use App\Jobs\MediaPipeline\MediaSyncLicensePipeline;
use App\Services\AccountService;

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
		return view('settings.import.home');
	}

	public function dataImportInstagram()
	{
		abort(404);
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
		$user->email = $user->id;
		$user->password = '';
		$user->status = 'delete';
		$profile->status = 'delete';
		$user->delete_after = $ts;
		$profile->delete_after = $ts;
		$user->save();
		$profile->save();
		Cache::forget('profiles:private');
		AccountService::del($profile->id);
		Auth::logout();
		DeleteAccountPipeline::dispatch($user)->onQueue('low');
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
			$cookie = Cookie::make('dark-mode', 'true', 43800);
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
			return redirect(route('settings'))->with('error', 'An error occured. Please try again later.');
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
		return redirect(route('settings'))->with('status', 'Sponsor settings successfully updated!');
	}

	public function timelineSettings(Request $request)
	{
		$pid = $request->user()->profile_id;
		$top = Redis::zscore('pf:tl:top', $pid) != false;
		$replies = Redis::zscore('pf:tl:replies', $pid) != false;
		return view('settings.timeline', compact('top', 'replies'));
	}

	public function updateTimelineSettings(Request $request)
	{
		$pid = $request->user()->profile_id;
		$top = $request->has('top') && $request->input('top') === 'on';
		$replies = $request->has('replies') && $request->input('replies') === 'on';

		if($top) {
			Redis::zadd('pf:tl:top', $pid, $pid);
		} else {
			Redis::zrem('pf:tl:top', $pid);
		}

		if($replies) {
			Redis::zadd('pf:tl:replies', $pid, $pid);
		} else {
			Redis::zrem('pf:tl:replies', $pid);
		}
		return redirect(route('settings'))->with('status', 'Timeline settings successfully updated!');
	}

	public function mediaSettings(Request $request)
	{
		$setting = UserSetting::whereUserId($request->user()->id)->firstOrFail();
		$compose = $setting->compose_settings ? (
			is_string($setting->compose_settings) ? json_decode($setting->compose_settings, true) : $setting->compose_settings
			) : [
			'default_license' => null,
			'media_descriptions' => false
		];
		return view('settings.media', compact('compose'));
	}

	public function updateMediaSettings(Request $request)
	{
		$this->validate($request, [
			'default' => 'required|int|min:1|max:16',
			'sync' => 'nullable',
			'media_descriptions' => 'nullable'
		]);

		$license = $request->input('default');
		$sync = $request->input('sync') == 'on';
		$media_descriptions = $request->input('media_descriptions') == 'on';
		$uid = $request->user()->id;

		$setting = UserSetting::whereUserId($uid)->firstOrFail();
		$compose = is_string($setting->compose_settings) ? json_decode($setting->compose_settings, true) : $setting->compose_settings;
		$changed = false;

		if($sync) {
			$key = 'pf:settings:mls_recently:'.$uid;
			if(Cache::get($key) == 2) {
				$msg = 'You can only sync licenses twice per 24 hours. Try again later.';
				return redirect(route('settings'))
					->with('error', $msg);
			}
		}

		if(!isset($compose['default_license']) || $compose['default_license'] !== $license) {
			$compose['default_license'] = (int) $license;
			$changed = true;
		}

		if(!isset($compose['media_descriptions']) || $compose['media_descriptions'] !== $media_descriptions) {
			$compose['media_descriptions'] = $media_descriptions;
			$changed = true;
		}

		if($changed) {
			$setting->compose_settings = $compose;
			$setting->save();
			Cache::forget('profile:compose:settings:' . $request->user()->id);
		}

		if($sync) {
			$val = Cache::has($key) ? 2 : 1;
			Cache::put($key, $val, 86400);
			MediaSyncLicensePipeline::dispatch($uid, $license);
			return redirect(route('settings'))->with('status', 'Media licenses successfully synced! It may take a few minutes to take effect for every post.');
		}

		return redirect(route('settings'))->with('status', 'Media settings successfully updated!');
	}

}

