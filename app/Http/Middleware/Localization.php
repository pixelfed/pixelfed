<?php

namespace App\Http\Middleware;

use App\Util\Localization\Localization as LocalizationUtil;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Session::has('locale')) {
            app()->setLocale(LocalizationUtil::normalizeLocale(Session::get('locale')));
        }

        return $next($request);
    }
}
