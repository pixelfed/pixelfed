<?php

namespace App\Http\Controllers\Settings;

use App\Follower;
use App\Profile;
use App\Services\AccountService;
use App\Services\RelationshipService;
use App\UserFilter;
use Auth;
use Cache;
use DB;
use Illuminate\Http\Request;

trait PrivacySettings
{
    public function privacy()
    {
        $user = Auth::user();
        $settings = $user->settings;
        $profile = $user->profile;
        $is_private = $profile->is_private;
        $cachedSettings = AccountService::getAccountSettings($profile->id);
        $settings['is_private'] = (bool) $is_private;
        if ($cachedSettings && isset($cachedSettings['disable_embeds'])) {
            $settings['disable_embeds'] = (bool) $cachedSettings['disable_embeds'];
        } else {
            $settings['disable_embeds'] = false;
        }

        return view('settings.privacy', compact('settings', 'profile'));
    }

    public function privacyStore(Request $request)
    {
        $settings = $request->user()->settings;
        $profile = $request->user()->profile;
        $other = $settings->other;
        $fields = [
            'is_private',
            'crawlable',
            'public_dm',
            'show_profile_follower_count',
            'show_profile_following_count',
            'indexable',
            'show_atom',
        ];

        $profile->indexable = $request->input('indexable') == 'on';
        $profile->is_suggestable = $request->input('is_suggestable') == 'on';
        $profile->save();

        if ($request->has('disable_embeds')) {
            $other['disable_embeds'] = true;
            $settings->other = $other;
            $settings->save();
        } else {
            $other['disable_embeds'] = false;
            $settings->other = $other;
            $settings->save();
        }

        foreach ($fields as $field) {
            $form = $request->input($field);
            if ($field == 'is_private') {
                if ($form == 'on') {
                    $profile->{$field} = true;
                    $settings->show_guests = false;
                    $settings->show_discover = false;
                    $profile->save();
                } else {
                    $profile->{$field} = false;
                    $profile->save();
                }
                Cache::forget('profiles:private');
            } elseif ($field == 'crawlable') {
                if ($form == 'on') {
                    $settings->{$field} = false;
                } else {
                    $settings->{$field} = true;
                }
            } elseif ($field == 'public_dm') {
                if ($form == 'on') {
                    $settings->{$field} = true;
                } else {
                    $settings->{$field} = false;
                }
            } elseif ($field == 'indexable') {

            } else {
                if ($form == 'on') {
                    $settings->{$field} = true;
                } else {
                    $settings->{$field} = false;
                }
            }
            $settings->save();
        }
        $pid = $profile->id;
        Cache::forget('profile:settings:'.$pid);
        Cache::forget('user:account:id:'.$profile->user_id);
        Cache::forget('profile:follower_count:'.$pid);
        Cache::forget('profile:following_count:'.$pid);
        Cache::forget('profile:atom:enabled:'.$pid);
        Cache::forget('profile:embed:'.$pid);
        Cache::forget('pf:acct:settings:hidden-followers:'.$pid);
        Cache::forget('pf:acct:settings:hidden-following:'.$pid);
        Cache::forget('pf:acct-trans:hideFollowing:'.$pid);
        Cache::forget('pf:acct-trans:hideFollowers:'.$pid);
        Cache::forget('pfc:cached-user:wt:'.strtolower($profile->username));
        Cache::forget('pfc:cached-user:wot:'.strtolower($profile->username));
        AccountService::forgetAccountSettings($profile->id);

        return redirect(route('settings.privacy'))->with('status', 'Settings successfully updated!');
    }

    public function mutedUsers()
    {
        $pid = Auth::user()->profile->id;
        $ids = (new UserFilter)->mutedUserIds($pid);
        $users = Profile::whereIn('id', $ids)->simplePaginate(15);

        return view('settings.privacy.muted', compact('users'));
    }

    public function mutedUsersUpdate(Request $request)
    {
        $this->validate($request, [
            'profile_id' => 'required|integer|min:1',
        ]);
        $fid = $request->input('profile_id');
        $pid = Auth::user()->profile->id;
        DB::transaction(function () use ($fid, $pid) {
            $filter = UserFilter::whereUserId($pid)
                ->whereFilterableId($fid)
                ->whereFilterableType('App\Profile')
                ->whereFilterType('mute')
                ->firstOrFail();
            $filter->delete();
        });
        RelationshipService::refresh($pid, $fid);

        return redirect()->back();
    }

    public function blockedUsers()
    {
        $pid = Auth::user()->profile->id;
        $ids = (new UserFilter)->blockedUserIds($pid);
        $users = Profile::whereIn('id', $ids)->simplePaginate(15);

        return view('settings.privacy.blocked', compact('users'));
    }

    public function blockedUsersUpdate(Request $request)
    {
        $this->validate($request, [
            'profile_id' => 'required|integer|min:1',
        ]);
        $fid = $request->input('profile_id');
        $pid = Auth::user()->profile->id;
        DB::transaction(function () use ($fid, $pid) {
            $filter = UserFilter::whereUserId($pid)
                ->whereFilterableId($fid)
                ->whereFilterableType('App\Profile')
                ->whereFilterType('block')
                ->firstOrFail();
            $filter->delete();
        });
        RelationshipService::refresh($pid, $fid);

        return redirect()->back();
    }

    public function blockedInstances()
    {
        // deprecated
        abort(404);
    }

    public function domainBlocks()
    {
        return view('settings.privacy.domain-blocks');
    }

    public function blockedInstanceStore(Request $request)
    {
        // deprecated
        abort(404);
    }

    public function blockedInstanceUnblock(Request $request)
    {
        // deprecated
        abort(404);
    }

    public function blockedKeywords()
    {
        return view('settings.privacy.blocked-keywords');
    }

    public function privateAccountOptions(Request $request)
    {
        $this->validate($request, [
            'mode' => 'required|string|in:keep-all,mutual-only,only-followers,remove-all',
            'duration' => 'required|integer|min:60|max:525600',
        ]);
        $mode = $request->input('mode');
        $duration = $request->input('duration');
        // $newRequests = $request->input('newrequests');

        $profile = Auth::user()->profile;
        $settings = Auth::user()->settings;

        if ($mode !== 'keep-all') {
            switch ($mode) {
                case 'mutual-only':
                    $following = $profile->following()->pluck('profiles.id');
                    Follower::whereFollowingId($profile->id)->whereNotIn('profile_id', $following)->delete();
                    break;

                case 'only-followers':
                    $ts = now()->subMinutes($duration);
                    Follower::whereFollowingId($profile->id)->where('created_at', '>', $ts)->delete();
                    break;

                case 'remove-all':
                    Follower::whereFollowingId($profile->id)->delete();
                    break;

                default:
                    // code...
                    break;
            }
        }
        $profile->is_private = true;
        $settings->show_guests = false;
        $settings->show_discover = false;
        $settings->save();
        $profile->save();
        Cache::forget('profiles:private');

        return [200];
    }
}
