<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImportController extends Controller
{
	use Import\Instagram, Import\Mastodon;

	public function __construct()
	{
		$this->middleware('auth');
	}

}
