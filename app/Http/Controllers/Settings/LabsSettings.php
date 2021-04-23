<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Cookie;
use Illuminate\Support\Facades\Redis;
use App\Services\SuggestionService;

trait LabsSettings {

	public function __constructor()
	{
		$this->middleware('auth');
	}

	public function labs(Request $request)
	{
		$profile = $request->user()->profile;
		return view('settings.labs', compact('profile'));
	}

	public function labsStore(Request $request)
	{
		$this->validate($request, [
			'profile_layout' => 'nullable',
			'dark_mode'	=> 'nullable',
			'profile_suggestions' => 'nullable',
			'moment_bg' => 'nullable'
		]);

		$changes = false;

		$profile = $request->user()->profile;

		$cookie = Cookie::forget('dark-mode');
		if($request->has('dark_mode')) {
			if($request->dark_mode == 'on') {
				$cookie = Cookie::make('dark-mode', true, 43800);
			} 
		}

		if($request->has('profile_layout')) {
			
		} else {
			$profile->profile_layout = null;
			$changes = true;
		}

		if($request->has('profile_suggestions')) {
			if($profile->is_suggestable == false) {
				$profile->is_suggestable = true;
				$changes = true;
				if($profile->statuses->count() > 0) {
					SuggestionService::set($profile->id);
				}
			} 
		} else {
			$profile->is_suggestable = false;
			$changes = true;
			SuggestionService::del($profile->id);
		}

		if($request->has('moment_bg') && $profile->profile_layout == 'moment') {
			$bg = in_array($request->input('moment_bg'), $this->momentBackgrounds()) ? $request->input('moment_bg') : 'default';
			$profile->header_bg = $bg;
			$changes = true;
		}

		if($changes == true) {
			$profile->save();
		}

		return redirect(route('settings.labs'))
			->with('status', 'Labs preferences successfully updated!')
			->cookie($cookie);
	}

	protected function momentBackgrounds()
	{
		return [
			'default',
			'azure',
			'passion',
			'reef',
			'lush',
			'neon',
			'flare',
			'morning',
			'tranquil',
			'mauve',
			'argon',
			'royal'
		];
	}
}
