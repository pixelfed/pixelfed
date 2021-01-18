<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth, Storage, URL;
use App\Media;
use Image as Intervention;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;

class MediaController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
	}

	public function index(Request $request)
	{
		//return view('settings.drive.index');
	}

	public function composeUpdate(Request $request, $id)
	{
		abort(404);
	}	
}
