<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ImportController extends Controller implements HasMiddleware
{
    use Import\Instagram, Import\Mastodon;

    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }
}
