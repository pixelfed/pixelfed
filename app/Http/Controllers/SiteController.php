<?php

namespace App\Http\Controllers;

use App;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function changeLocale(Request $request, $locale)
    {
        if(!App::isLocale($locale)) {
          return redirect()->back();
        }
        App::setLocale($locale);
        return redirect()->back();
    }
}
