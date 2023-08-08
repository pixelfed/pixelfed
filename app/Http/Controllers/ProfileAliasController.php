<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Util\Lexer\Nickname;
use App\Util\Webfinger\WebfingerUrl;
use App\Models\ProfileAlias;
use App\Services\WebfingerService;

class ProfileAliasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $aliases = $request->user()->profile->aliases;
        return view('settings.aliases.index', compact('aliases'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'acct' => 'required'
        ]);

        $acct = $request->input('acct');

        if($request->user()->profile->aliases->count() >= 3) {
            return back()->with('error', 'You can only add 3 account aliases.');
        }

        $webfingerService = WebfingerService::lookup($acct);
        if(!$webfingerService || !isset($webfingerService['url'])) {
            return back()->with('error', 'Invalid account, cannot add alias at this time.');
        }
        $alias = new ProfileAlias;
        $alias->profile_id = $request->user()->profile_id;
        $alias->acct = $acct;
        $alias->uri = $webfingerService['url'];
        $alias->save();

        return back()->with('status', 'Successfully added alias!');
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'acct' => 'required',
            'id' => 'required|exists:profile_aliases'
        ]);

        $alias = ProfileAlias::where('profile_id', $request->user()->profile_id)
            ->where('acct', $request->input('acct'))
            ->findOrFail($request->input('id'));

        $alias->delete();

        return back()->with('status', 'Successfully deleted alias!');
    }
}
