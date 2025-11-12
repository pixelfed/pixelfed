<?php

namespace App\Http\Controllers;

use App\Models\ProfileAlias;
use App\Models\ProfileMigration;
use App\Services\AccountService;
use App\Services\WebfingerService;
use App\Util\Lexer\Nickname;
use Cache;
use Illuminate\Http\Request;

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
            'acct' => 'required',
        ]);

        $acct = $request->input('acct');

        $nn = Nickname::normalizeProfileUrl($acct);
        if (! $nn) {
            return back()->with('error', 'Invalid account alias.');
        }

        if ($nn['domain'] === config('pixelfed.domain.app')) {
            if (strtolower($nn['username']) == ($request->user()->username)) {
                return back()->with('error', 'You cannot add an alias to your own account.');
            }
        }

        if ($request->user()->profile->aliases->count() >= 3) {
            return back()->with('error', 'You can only add 3 account aliases.');
        }

        $webfingerService = WebfingerService::lookup($acct);
        $webfingerUrl = WebfingerService::rawGet($acct);

        if (! $webfingerService || ! isset($webfingerService['url']) || ! $webfingerUrl) {
            return back()->with('error', 'Invalid account, cannot add alias at this time.');
        }
        $alias = new ProfileAlias;
        $alias->profile_id = $request->user()->profile_id;
        $alias->acct = $acct;
        $alias->uri = $webfingerUrl;
        $alias->save();

        Cache::forget('pf:activitypub:user-object:by-id:'.$request->user()->profile_id);

        return back()->with('status', 'Successfully added alias!');
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'acct' => 'required',
            'id' => 'required|exists:profile_aliases',
        ]);
        $pid = $request->user()->profile_id;
        $acct = $request->input('acct');
        $alias = ProfileAlias::where('profile_id', $pid)
            ->where('acct', $acct)
            ->findOrFail($request->input('id'));
        $migration = ProfileMigration::whereProfileId($pid)
            ->whereAcct($acct)
            ->first();
        if ($migration) {
            $request->user()->profile->update([
                'moved_to_profile_id' => null,
            ]);
        }

        $alias->delete();
        Cache::forget('pf:activitypub:user-object:by-id:'.$pid);
        AccountService::del($pid);

        return back()->with('status', 'Successfully deleted alias!');
    }
}
