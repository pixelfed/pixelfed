<?php

namespace App\Http\Controllers;

class ImportController extends Controller
{
    use Import\Instagram, Import\Mastodon;

    public function __construct()
    {
        $this->middleware('auth');
    }
}
