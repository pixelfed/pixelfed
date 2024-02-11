<?php

namespace App\Http\Controllers\Import;

trait Mastodon
{
    public function mastodon()
    {
        if (config_cache('pixelfed.import.instagram.enabled') != true) {
            abort(404, 'Feature not enabled');
        }

        return view('settings.import.mastodon.home');
    }
}
