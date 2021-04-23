<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Http\Controllers\Import;

use Illuminate\Http\Request;

trait Mastodon
{
    public function mastodon()
    {
      return view('settings.import.mastodon.home');
    }
}
