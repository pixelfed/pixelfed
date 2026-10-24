<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DeckController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
