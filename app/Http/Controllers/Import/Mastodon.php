<?php

namespace App\Http\Controllers\Import;

use Illuminate\Http\Request;

trait Mastodon
{
    public function mastodon()
    {
        return view('settings.import.mastodon.home');
    }
}
