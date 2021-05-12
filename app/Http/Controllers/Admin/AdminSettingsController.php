<?php

namespace App\Http\Controllers\Admin;

use Artisan, Cache, DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\{Comment, Like, Media, Page, Profile, Report, Status, User};
use App\Http\Controllers\Controller;
use App\Util\Lexer\PrettyNumber;
use App\Models\ConfigCache;
use App\Services\ConfigCacheService;

trait AdminSettingsController
{
	public function settings(Request $request)
	{
		$name = ConfigCacheService::get('app.name');
		$short_description = ConfigCacheService::get('app.short_description');
		$description = ConfigCacheService::get('app.description');
		$types = explode(',', ConfigCacheService::get('pixelfed.media_types'));
		$jpeg = in_array('image/jpg', $types) ? true : in_array('image/jpeg', $types);
		$png = in_array('image/png', $types);
		$gif = in_array('image/gif', $types);
		$mp4 = in_array('video/mp4', $types);

		return view('admin.settings.home', compact(
			'name',
			'short_description',
			'description',
			'jpeg',
			'png',
			'gif',
			'mp4'
		));
	}

	public function settingsHomeStore(Request $request)
	{
		$this->validate($request, [
			'name' => 'nullable|string',
			'short_description' => 'nullable',
			'long_description' => 'nullable',
			'max_photo_size' => 'nullable|integer|min:1',
			'max_album_length' => 'nullable|integer|min:1|max:100',
			'image_quality' => 'nullable|integer|min:1|max:100',
			'type_jpeg' => 'nullable',
			'type_png' => 'nullable',
			'type_gif' => 'nullable',
			'type_mp4' => 'nullable',
		]);

		$media_types = explode(',', config_cache('pixelfed.media_types'));
		$media_types_original = $media_types;

		$mimes = [
			'type_jpeg' => 'image/jpeg',
			'type_png' => 'image/png',
			'type_gif' => 'image/gif',
			'type_mp4' => 'video/mp4',
		];

		foreach ($mimes as $key => $value) {
			if($request->input($key) == 'on') {
				if(!in_array($value, $media_types)) {
					array_push($media_types, $value);
				}
			} else {
				$media_types = array_diff($media_types, [$value]);
			}
		}

		if($media_types !== $media_types_original) {
			ConfigCacheService::put('pixelfed.media_types', implode(',', array_unique($media_types)));
		}

		$keys = [
			'name' => 'app.name',
			'short_description' => 'app.short_description',
			'long_description' => 'app.description',
			'max_photo_size' => 'pixelfed.max_photo_size',
			'max_album_length' => 'pixelfed.max_album_length',
			'image_quality' => 'pixelfed.image_quality',
			'account_limit' => 'pixelfed.max_account_size',
			'custom_css' => 'uikit.custom.css',
			'custom_js' => 'uikit.custom.js'
		];

		foreach ($keys as $key => $value) {
			$cc = ConfigCache::whereK($value)->first();
			$val = $request->input($key);
			if($cc && $cc->v != $val) {
				ConfigCacheService::put($value, $val);
			}
		}

		$bools = [
			'activitypub' => 'federation.activitypub.enabled',
			'open_registration' => 'pixelfed.open_registration',
			'mobile_apis' => 'pixelfed.oauth_enabled',
			'stories' => 'instance.stories.enabled',
			'ig_import' => 'pixelfed.import.instagram.enabled',
			'spam_detection' => 'pixelfed.bouncer.enabled',
			'require_email_verification' => 'pixelfed.enforce_email_verification',
			'enforce_account_limit' => 'pixelfed.enforce_account_limit',
			'show_custom_css' => 'uikit.show_custom.css',
			'show_custom_js' => 'uikit.show_custom.js',
		];

		foreach ($bools as $key => $value) {
			$active = $request->input($key) == 'on';

			if(config_cache($value) !== $active) {
				ConfigCacheService::put($value, (bool) $active);
			}
		}

		Cache::forget('api:site:configuration:_v0.2');

		return redirect('/i/admin/settings');
	}

	public function settingsBackups(Request $request)
	{
		$path = storage_path('app/'.config('app.name'));
		$files = is_dir($path) ? new \DirectoryIterator($path) : [];
		return view('admin.settings.backups', compact('files'));
	}

	public function settingsConfig(Request $request)
	{
		$editor = config('pixelfed.admin.env_editor');
		$config = !$editor ? false : file_get_contents(base_path('.env'));
		$backup = !$editor ? false : (is_file(base_path('.env.backup')) ? file_get_contents(base_path('.env.backup')) : false);
		return view('admin.settings.config', compact('editor', 'config', 'backup'));
	}

	public function settingsConfigStore(Request $request)
	{
		if(config('pixelfed.admin.env_editor') !== true) {
			abort(400);
		}
		$res = $request->input('res');

		$old = file_get_contents(app()->environmentFilePath());
		if(empty($old) || $old != $res) {
			$oldFile = fopen(app()->environmentFilePath().'.backup', 'w');
			fwrite($oldFile, $old);
			fclose($oldFile);
		}

		$file = fopen(app()->environmentFilePath(), 'w');
		fwrite($file, $res);
		fclose($file);
		Artisan::call('config:cache');
		return ['msg' => 200];
	}

	public function settingsConfigRestore(Request $request)
	{
		if(config('pixelfed.admin.env_editor') !== true) {
			abort(400);
		}
		$res = file_get_contents(app()->environmentFilePath().'.backup');
		if(empty($res)) {
			abort(400, 'No backup exists.');
		}
		$file = fopen(app()->environmentFilePath(), 'w');
		fwrite($file, $res);
		fclose($file);
		Artisan::call('config:cache');
		return ['msg' => 200];
	}

	public function settingsMaintenance(Request $request)
	{
		return view('admin.settings.maintenance');
	}

	public function settingsStorage(Request $request)
	{
		$storage = [];
		return view('admin.settings.storage', compact('storage'));
	}

	public function settingsFeatures(Request $request)
	{
		return view('admin.settings.features');
	}

	public function settingsPages(Request $request)
	{
		$pages = Page::orderByDesc('updated_at')->paginate(10);
		return view('admin.pages.home', compact('pages'));
	}

	public function settingsPageEdit(Request $request)
	{
		return view('admin.pages.edit');
	}

	public function settingsSystem(Request $request)
	{
		$sys = [
			'pixelfed' => config('pixelfed.version'),
			'php' => phpversion(),
			'laravel' => app()->version(),
		];
		switch (config('database.default')) {
			case 'pgsql':
			$sys['database'] = [
				'name' => 'Postgres',
				'version' => explode(' ', DB::select(DB::raw('select version();'))[0]->version)[1]
			];
			break;

			case 'mysql':
			$sys['database'] = [
				'name' => 'MySQL',
				'version' => DB::select( DB::raw("select version()") )[0]->{'version()'}
			];
			break;

			default:
			$sys['database'] = [
				'name' => 'Unknown',
				'version' => '?'
			];
			break;
		}
		return view('admin.settings.system', compact('sys'));
	}
}
