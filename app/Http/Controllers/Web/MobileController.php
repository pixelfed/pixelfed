<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\ManagesCachedPages;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class MobileController extends Controller
{
    use ManagesCachedPages;

    public function terms(Request $request)
    {
        $page = Cache::remember('site:terms', now()->addDays(120), function () {
            return $this->cachedPage('/site/terms');
        });

        return View::make('mobile.terms')->with(compact('page'))->render();
    }

    public function privacy(Request $request)
    {
        $page = Cache::remember('site:privacy', now()->addDays(120), function () {
            return $this->cachedPage('/site/privacy');
        });

        return View::make('mobile.privacy')->with(compact('page'))->render();
    }
}
