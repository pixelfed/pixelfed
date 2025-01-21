<?php

namespace App\Http\Controllers;

use App\Http\Resources\DirectoryProfile;
use App\Profile;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function directoryRedirect(Request $request)
    {
        if ($request->user()) {
            return redirect('/');
        }

        abort_if((bool) config_cache('instance.landing.show_directory') == false, 404);

        return view('site.index');
    }

    public function exploreRedirect(Request $request)
    {
        if ($request->user()) {
            return redirect('/');
        }

        abort_if((bool) config_cache('instance.landing.show_explore') == false, 404);

        return view('site.index');
    }

    public function getDirectoryApi(Request $request)
    {
        abort_if((bool) config_cache('instance.landing.show_directory') == false, 404);

        return DirectoryProfile::collection(
            Profile::whereNull('domain')
                ->whereIsSuggestable(true)
                ->orderByDesc('updated_at')
                ->cursorPaginate(20)
        );
    }
}
