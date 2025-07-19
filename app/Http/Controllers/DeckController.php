<?php

namespace App\Http\Controllers;

class DeckController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function home()
    {
        return view('deck.index');
    }

    public function insights()
    {
        return view('deck.insights.index');
    }
}
