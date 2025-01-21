<?php

namespace App\Http\Controllers;

use App\Util\ActivityPub\Helpers;
use Illuminate\Http\Request;

class AuthorizeInteractionController extends Controller
{
    public function get(Request $request)
    {
        $request->validate([
            'uri' => 'required|url',
        ]);

        abort_unless((bool) config_cache('federation.activitypub.enabled'), 404);

        $uri = Helpers::validateUrl($request->input('uri'), true);
        abort_unless($uri, 404);

        if (! $request->user()) {
            return redirect('/login?next='.urlencode($uri));
        }

        $status = Helpers::statusFetch($uri);
        if ($status && isset($status['id'])) {
            return redirect('/i/web/post/'.$status['id']);
        }

        $profile = Helpers::profileFetch($uri);
        if ($profile && isset($profile['id'])) {
            return redirect('/i/web/profile/'.$profile['id']);
        }

        return redirect('/i/web');
    }
}
