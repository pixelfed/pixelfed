<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;

trait LabsSettings
{
    public function __constructor()
    {
        $this->middleware('auth');
    }

    public function labs(Request $request)
    {
        $profile = $request->user()->profile;

        return view('settings.labs', ['profile' => $profile]);
    }

    public function labsStore(Request $request)
    {
        return redirect(route('settings.labs'))
            ->with('status', 'Labs preferences successfully updated!');
    }
}
