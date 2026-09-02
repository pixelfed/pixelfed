<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Contracts\View\View;

class DeckController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
        ];
    }

    public function home(): View
    {
        return view('deck.index');
    }

    public function insights(): View
    {
        return view('deck.insights.index');
    }
}
