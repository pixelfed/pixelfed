<?php

namespace App\Http\Controllers\Settings;

use App\AccountLog;
use App\EmailVerification;
use App\Instance;
use App\Follower;
use App\Media;
use App\Profile;
use App\User;
use App\UserFilter;
use App\Util\Lexer\PrettyNumber;
use App\Util\ActivityPub\Helpers;
use Auth, Cache, DB;
use Illuminate\Http\Request;
use App\Models\UserDomainBlock;

trait PrivacySettings
{

    public function privacy()
    {
        $user = Auth::user();
        $settings = $user->settings;
        $profile = $user->profile;
        $is_private = $profile->is_private;
        $settings['is_private'] = (bool) $is_private;

        return view('settings.privacy', compact('settings', 'profile'));
    }

    public function privacyStore(Request $request)
    {
        $settings = $request->user()->settings;
        $profile = $request->user()->profile;
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
        Cache::forget('profile:settings:' . $profile->id);
        Cache::forget('user:account:id:' . $profile->user_id);
        Cache::forget('profile:follower_count:' . $profile->id);
        Cache::forget('profile:following_count:' . $profile->id);
        Cache::forget('profile:atom:enabled:' . $profile->id);
        Cache::forget('profile:embed:' . $profile->id);
        Cache::forget('pf:acct:settings:hidden-followers:' . $profile->id);
        Cache::forget('pf:acct:settings:hidden-following:' . $profile->id);
        return redirect(route('settings.privacy'))->with('status', 'Settings successfully updated!');
    }

    public function mutedUsers()
    {   
        $pid = Auth::user()->profile->id;
        $ids = (new UserFilter())->mutedUserIds($pid);
        $users = Profile::whereIn('id', $ids)->simplePaginate(15);
        return view('settings.privacy.muted', compact('users'));
    }

    public function mutedUsersUpdate(Request $request)
    {   
        $this->validate($request, [
            'profile_id' => 'required|integer|min:1'
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
        return redirect()->back();
    }

    public function blockedUsers()
    {
        $pid = Auth::user()->profile->id;
        $ids = (new UserFilter())->blockedUserIds($pid);
        $users = Profile::whereIn('id', $ids)->simplePaginate(15);
        return view('settings.privacy.blocked', compact('users'));
    }


    public function blockedUsersUpdate(Request $request)
    {   
        $this->validate($request, [
            'profile_id' => 'required|integer|min:1'
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

        if($mode !== 'keep-all') {
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
                    # code...
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
