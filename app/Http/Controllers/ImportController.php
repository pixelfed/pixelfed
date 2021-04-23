<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImportController extends Controller
{
	use Import\Instagram, Import\Mastodon;

	public function __construct()
	{
		$this->middleware('auth');

		if(config('pixelfed.import.instagram.enabled') != true) {
			abort(404, 'Feature not enabled');
		}
	}

}
