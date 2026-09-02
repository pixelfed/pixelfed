<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
class ImportController extends Controller
{
    use Import\Instagram, Import\Mastodon;
}
