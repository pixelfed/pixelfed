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
		$this->validate($request, [
			'file'      => function() {
				return [
					'required',
					'mimes:' . config('pixelfed.media_types'),
					'max:' . config('pixelfed.max_photo_size'),
				];
			},
		]);

		$user = Auth::user();

		$photo = $request->file('file');

		$media = Media::whereUserId($user->id)
			->whereProfileId($user->profile_id)
			->whereNull('status_id')
			->findOrFail($id);

		$media->version = 2;
		$media->save();

		$fragments = explode('/', $media->media_path);
		$name = last($fragments);
		array_pop($fragments);
		$dir = implode('/', $fragments);
		$path = $photo->storeAs($dir, $name);
        $res = [];
        $res['url'] =  URL::temporarySignedRoute(
            'temp-media', now()->addHours(1), ['profileId' => $media->profile_id, 'mediaId' => $media->id, 'timestamp' => time()]
        );
        ImageOptimize::dispatch($media);
		return $res;

	}	
}
