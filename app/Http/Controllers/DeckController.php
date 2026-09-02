<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
class DeckController extends Controller
{
    public function home(): View
    {
        return view('deck.index');
    }

    public function insights(): View
    {
        return view('deck.insights.index');
    }
}
