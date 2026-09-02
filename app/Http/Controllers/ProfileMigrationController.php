<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileMigrationStoreRequest;
use App\Jobs\ProfilePipeline\ProfileMigrationDeliverMoveActivityPipeline;
use App\Jobs\ProfilePipeline\ProfileMigrationMoveFollowersPipeline;
use App\Models\ProfileAlias;
use App\Models\ProfileMigration;
use App\Services\AccountService;
use App\Services\WebfingerService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

#[Middleware('auth')]
class ProfileMigrationController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        abort_if((bool) config_cache('federation.activitypub.enabled') === false, 404);
        if ((bool) config_cache('federation.migration') === false) {
            return redirect(route('help.account-migration'));
        }
        $hasExistingMigration = ProfileMigration::whereProfileId($request->user()->profile_id)
            ->where('created_at', '>', now()->subDays(30))
            ->exists();

        return view('settings.migration.index', compact('hasExistingMigration'));
    }

    public function store(ProfileMigrationStoreRequest $request): RedirectResponse
    {
        abort_if((bool) config_cache('federation.activitypub.enabled') === false, 404);
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
        $migration = ProfileMigration::create([
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
        Cache::forget('pfc:cached-user:wt:'.strtolower($user->profile->username));
        Cache::forget('pfc:cached-user:wot:'.strtolower($user->profile->username));

        Bus::batch([
            [
                new ProfileMigrationDeliverMoveActivityPipeline($migration, $user->profile, $newAccount),
            ],
            [
                new ProfileMigrationMoveFollowersPipeline($user->profile_id, $newAccount->id),
            ],
        ])->onQueue('move')->dispatch();

        return redirect()->back()->with(['status' => 'Succesfully migrated account!']);
    }
}
