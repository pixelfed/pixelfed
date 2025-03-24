<?php

namespace App\Http\Controllers;

use App\Media;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        abort(404);
    }

    public function composeUpdate(Request $request, $id)
    {
        abort(400, 'Endpoint deprecated');
    }

    public function fallbackRedirect(Request $request, $pid, $mhash, $uhash, $f)
    {
        if (! (bool) config_cache('pixelfed.cloud_storage')) {
            return redirect('/storage/no-preview.png', 302);
        }

        $path = 'public/m/_v2/'.$pid.'/'.$mhash.'/'.$uhash.'/'.$f;

        $media = Media::whereProfileId($pid)
            ->whereMediaPath($path)
            ->whereNotNull('cdn_url')
            ->first();

        if (! $media) {
            return redirect('/storage/no-preview.png', 302);
        }

        return redirect()->away($media->cdn_url, 302);
    }
}
