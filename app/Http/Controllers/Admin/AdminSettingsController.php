<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers\Admin;

use Artisan, Cache, DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\{Comment, Like, Media, Page, Profile, Report, Status, User};
use App\Http\Controllers\Controller;
use App\Util\Lexer\PrettyNumber;

trait AdminSettingsController
{
	public function settings(Request $request)
	{
		return view('admin.settings.home');
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

	public function settingsHomeStore(Request $request)
	{
		$this->validate($request, [
			'APP_NAME' => 'required|string',
		]);
		// Artisan::call('config:clear');
		return redirect()->back();
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
