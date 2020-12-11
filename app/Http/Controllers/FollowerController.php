<?php

namespace App\Http\Controllers;

use App\{
    Follower,
    FollowRequest,
    Profile,
    UserFilter
};
use Auth, Cache;
use Illuminate\Http\Request;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Util\ActivityPub\Helpers;

class FollowerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'item'     => 'required|string',
            'force'    => 'nullable|boolean',
        ]);
        $force = (bool) $request->input('force', true);
        $item = (int) $request->input('item');
        $url = $this->handleFollowRequest($item, $force);
        if($request->wantsJson() == true) {
            return response()->json(200);
        } else {
            return redirect($url);
        }
    }

    protected function handleFollowRequest($item, $force)
    {
        $user = Auth::user()->profile;

        $target = Profile::where('id', '!=', $user->id)->whereNull('status')->findOrFail($item);
        $private = (bool) $target->is_private;
        $remote = (bool) $target->domain;
        $blocked = UserFilter::whereUserId($target->id)
                ->whereFilterType('block')
                ->whereFilterableId($user->id)
                ->whereFilterableType('App\Profile')
                ->exists();

        if($blocked == true) {
            abort(400, 'You cannot follow this user.');
        }

        $isFollowing = Follower::whereProfileId($user->id)->whereFollowingId($target->id)->exists();

        if($private == true && $isFollowing == 0) {
            if($user->following()->count() >= Follower::MAX_FOLLOWING) {
                abort(400, 'You cannot follow more than ' . Follower::MAX_FOLLOWING . ' accounts');
            }

            if($user->following()->where('followers.created_at', '>', now()->subHour())->count() >= Follower::FOLLOW_PER_HOUR) {
                abort(400, 'You can only follow ' . Follower::FOLLOW_PER_HOUR . ' users per hour');
            }

            $follow = FollowRequest::firstOrCreate([
                'follower_id' => $user->id,
                'following_id' => $target->id
            ]);
            if($remote == true && config('federation.activitypub.remoteFollow') == true) {
                $this->sendFollow($user, $target);
            } 
        } elseif ($private == false && $isFollowing == 0) {
            if($user->following()->count() >= Follower::MAX_FOLLOWING) {
                abort(400, 'You cannot follow more than ' . Follower::MAX_FOLLOWING . ' accounts');
            }

            if($user->following()->where('followers.created_at', '>', now()->subHour())->count() >= Follower::FOLLOW_PER_HOUR) {
                abort(400, 'You can only follow ' . Follower::FOLLOW_PER_HOUR . ' users per hour');
            }
            $follower = new Follower();
            $follower->profile_id = $user->id;
            $follower->following_id = $target->id;
            $follower->save();

            if($remote == true && config('federation.activitypub.remoteFollow') == true) {
                $this->sendFollow($user, $target);
            } 
            FollowPipeline::dispatch($follower);
        } else {
            if($force == true) {
                $request = FollowRequest::whereFollowerId($user->id)->whereFollowingId($target->id)->exists();
                $follower = Follower::whereProfileId($user->id)->whereFollowingId($target->id)->exists();
                if($remote == true && $request && !$follower) {
                    $this->sendFollow($user, $target);
                }
                if($remote == true && $follower) {
                    $this->sendUndoFollow($user, $target);
                }
                Follower::whereProfileId($user->id)
                    ->whereFollowingId($target->id)
                    ->delete();
            }
        }

        Cache::forget('profile:following:'.$target->id);
        Cache::forget('profile:followers:'.$target->id);
        Cache::forget('profile:following:'.$user->id);
        Cache::forget('profile:followers:'.$user->id);
        Cache::forget('api:local:exp:rec:'.$user->id);
        Cache::forget('user:account:id:'.$target->user_id);
        Cache::forget('user:account:id:'.$user->user_id);
        Cache::forget('px:profile:followers-v1.3:'.$user->id);
        Cache::forget('px:profile:followers-v1.3:'.$target->id);
        Cache::forget('px:profile:following-v1.3:'.$user->id);
        Cache::forget('px:profile:following-v1.3:'.$target->id);
        Cache::forget('profile:follower_count:'.$target->id);
        Cache::forget('profile:follower_count:'.$user->id);
        Cache::forget('profile:following_count:'.$target->id);
        Cache::forget('profile:following_count:'.$user->id);

        return $target->url();
    }

    public function sendFollow($user, $target)
    {
        if($target->domain == null || $user->domain != null) {
            return;
        }

        $payload = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => $user->permalink('#follow/'.$target->id),
            'type'      => 'Follow',
            'actor'     => $user->permalink(),
            'object'    => $target->permalink()
        ];

        $inbox = $target->sharedInbox ?? $target->inbox_url;

        Helpers::sendSignedObject($user, $inbox, $payload);
    }

    public function sendUndoFollow($user, $target)
    {
        if($target->domain == null || $user->domain != null) {
            return;
        }

        $payload = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => $user->permalink('#follow/'.$target->id.'/undo'),
            'type'      => 'Undo',
            'actor'     => $user->permalink(),
            'object'    => [
                'id' => $user->permalink('#follows/'.$target->id),
                'actor' => $user->permalink(),
                'object' => $target->permalink(),
                'type' => 'Follow'
            ]
        ];

        $inbox = $target->sharedInbox ?? $target->inbox_url;

        Helpers::sendSignedObject($user, $inbox, $payload);
    }
}
