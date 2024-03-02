<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileMigrationStoreRequest;
use App\Jobs\ProfilePipeline\ProfileMigrationMoveFollowersPipeline;
use App\Models\ProfileAlias;
use App\Models\ProfileMigration;
use App\Services\AccountService;
use App\Services\WebfingerService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Http\Request;

class ProfileMigrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $hasExistingMigration = ProfileMigration::whereProfileId($request->user()->profile_id)
            ->where('created_at', '>', now()->subDays(30))
            ->exists();

        return view('settings.migration.index', compact('hasExistingMigration'));
    }

    public function store(ProfileMigrationStoreRequest $request)
    {
        $acct = WebfingerService::rawGet($request->safe()->acct);
        if (! $acct) {
            return redirect()->back()->withErrors(['acct' => 'The new account you provided is not responding to our requests.']);
        }
        $newAccount = Helpers::profileFetch($acct);
        if (! $newAccount) {
            return redirect()->back()->withErrors(['acct' => 'An error occured, please try again later. Code: res-failed-account-fetch']);
        }
        $user = $request->user();
        ProfileAlias::updateOrCreate([
            'profile_id' => $user->profile_id,
            'acct' => $request->safe()->acct,
            'uri' => $acct,
        ]);
        ProfileMigration::create([
            'profile_id' => $request->user()->profile_id,
            'acct' => $request->safe()->acct,
            'followers_count' => $request->user()->profile->followers_count,
            'target_profile_id' => $newAccount['id'],
        ]);
        $user->profile->update([
            'moved_to_profile_id' => $newAccount->id,
            'indexable' => false,
        ]);
        AccountService::del($user->profile_id);

        ProfileMigrationMoveFollowersPipeline::dispatch($user->profile_id, $newAccount->id);

        return redirect()->back()->with(['status' => 'Succesfully migrated account!']);
    }
}
