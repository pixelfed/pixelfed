<?php

namespace App\Http\Controllers;

use App\Page;
use Cache;
use Illuminate\Http\Request;
use View;

class MobileController extends Controller
{
    public function terms(Request $request)
    {
        $page = Cache::remember('site:terms', now()->addDays(120), function () {
            $slug = '/site/terms';

            return Page::whereSlug($slug)->whereActive(true)->first();
        });

        return View::make('mobile.terms')->with(compact('page'))->render();
    }

    public function privacy(Request $request)
    {
        $page = Cache::remember('site:privacy', now()->addDays(120), function () {
            $slug = '/site/privacy';

            return Page::whereSlug($slug)->whereActive(true)->first();
        });

        return View::make('mobile.privacy')->with(compact('page'))->render();
    }
}
