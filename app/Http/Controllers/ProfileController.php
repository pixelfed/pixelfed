<?php

namespace App\Http\Controllers;

use App\AccountInterstitial;
use App\Follower;
use App\FollowRequest;
use App\Profile;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\StatusService;
use App\Status;
use App\Story;
use App\Transformer\ActivityPub\ProfileTransformer;
use App\User;
use App\UserFilter;
use App\UserSetting;
use Auth;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use View;

class ProfileController extends Controller
{


    protected function buildProfile(Request $request, $user)
    {
        $carousel = (bool) $request->filled('carousel');
        $username = $user->username;
        $loggedIn = Auth::check();
        $isPrivate = false;
        $isBlocked = false;
        if (! $loggedIn) {
            $key = 'profile:settings:'.$user->id;
            $ttl = now()->addHours(6);
            $settings = Cache::remember($key, $ttl, function () use ($user) {
                return $user->user->settings;
            });

            if ($user->is_private == true) {
                $profile = null;

                return view('profile.private', compact('user'));
            }

            $owner = false;
            $is_following = false;

            $profile = $user;
            $settings = [
                'crawlable' => $settings->crawlable,
                'following' => [
                    'count' => $settings->show_profile_following_count,
                    'list' => $settings->show_profile_following,
                ],
                'followers' => [
                    'count' => $settings->show_profile_follower_count,
                    'list' => $settings->show_profile_followers,
                ],
            ];

            if($carousel) {
                return view('profile.show_carousel', compact('profile', 'settings'));
            }
            return view('profile.show', compact('profile', 'settings'));
        } else {
            $key = 'profile:settings:'.$user->id;
            $ttl = now()->addHours(6);
            $settings = Cache::remember($key, $ttl, function () use ($user) {
                return $user->user->settings;
            });

            if ($user->is_private == true) {
                $isPrivate = $this->privateProfileCheck($user, $loggedIn);
            }

            $isBlocked = $this->blockedProfileCheck($user);

            $owner = $loggedIn && Auth::id() === $user->user_id;
            $is_following = ($owner == false && Auth::check()) ? $user->followedBy(Auth::user()->profile) : false;

            if ($isPrivate == true || $isBlocked == true) {
                $requested = Auth::check() ? FollowRequest::whereFollowerId(Auth::user()->profile_id)
                    ->whereFollowingId($user->id)
                    ->exists() : false;

                return view('profile.private', compact('user', 'is_following', 'requested'));
            }

            $is_admin = is_null($user->domain) ? $user->user->is_admin : false;
            $profile = $user;
            $settings = [
                'crawlable' => $settings->crawlable,
                'following' => [
                    'count' => $settings->show_profile_following_count,
                    'list' => $settings->show_profile_following,
                ],
                'followers' => [
                    'count' => $settings->show_profile_follower_count,
                    'list' => $settings->show_profile_followers,
                ],
            ];
            if($carousel) {
                return view('profile.show_carousel', compact('profile', 'settings'));
            }
            return view('profile.show', compact('profile', 'settings'));
        }
    }

    protected function getCachedUser($username, $withTrashed = false)
    {
        $val = str_replace(['_', '.', '-'], '', $username);
        if (! ctype_alnum($val)) {
            return;
        }
        $hash = ($withTrashed ? 'wt:' : 'wot:').strtolower($username);

        return Cache::remember('pfc:cached-user:'.$hash, ($withTrashed ? 14400 : 900), function () use ($username, $withTrashed) {
            if (! $withTrashed) {
                return Profile::whereNull(['domain', 'status'])
                    ->whereUsername($username)
                    ->first();
            } else {
                return Profile::withTrashed()
                    ->whereNull('domain')
                    ->whereUsername($username)
                    ->first();
            }
        });
    }

    protected function privateProfileCheck(Profile $profile, $loggedIn)
    {
        if (! Auth::check()) {
            return true;
        }

        $user = Auth::user()->profile;
        if ($user->id == $profile->id || ! $profile->is_private) {
            return false;
        }

        $follows = Follower::whereProfileId($user->id)->whereFollowingId($profile->id)->exists();
        if ($follows == false) {
            return true;
        }

        return false;
    }

    public static function accountCheck(Profile $profile)
    {
        switch ($profile->status) {
            case 'disabled':
            case 'suspended':
            case 'delete':
                return view('profile.disabled');
                break;

            default:
                break;
        }

        return abort(404);
    }

    protected function blockedProfileCheck(Profile $profile)
    {
        $pid = Auth::user()->profile->id;
        $blocks = UserFilter::whereUserId($profile->id)
            ->whereFilterType('block')
            ->whereFilterableType('App\Profile')
            ->pluck('filterable_id')
            ->toArray();
        if (in_array($pid, $blocks)) {
            return true;
        }

        return false;
    }

    public function showActivityPub(Request $request, $user)
    {
        abort_if(! config_cache('federation.activitypub.enabled'), 404);
        abort_if(! $user, 404, 'Not found');
        abort_if($user->domain, 404);

        return Cache::remember('pf:activitypub:user-object:by-id:'.$user->id, 1800, function () use ($user) {
            $fractal = new Fractal\Manager();
            $resource = new Fractal\Resource\Item($user, new ProfileTransformer);
            $res = $fractal->createData($resource)->toArray();

            return response(json_encode($res['data']))->header('Content-Type', 'application/activity+json');
        });
    }
}
