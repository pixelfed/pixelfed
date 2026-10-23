<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\HashtagFollow;
use App\Services\HashtagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HashtagFollowController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request): array
    {
        $this->validate($request, [
            'name' => 'required|alpha_num|min:1|max:124|exists:hashtags,name',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        abort_if(! $profile, 422, 'Profile not available for this account.');

        $tag = $request->input('name');
        $pid = $user->profile_id ?? $profile->id;

        $hashtag = Hashtag::whereName($tag)->firstOrFail();

        $existing = HashtagFollow::whereProfileId($pid)
            ->whereHashtagId($hashtag->id)
            ->first();

        // Unfollow toggle must still work at the cap, so only enforce the limit
        // on the create branch (matching TagsController::followHashtag, keyed on
        // profile_id).
        if ($existing) {
            HashtagService::unfollow($profile->id, $hashtag->id);
            $existing->delete();

            return ['state' => 'deleted'];
        }

        abort_if(
            HashtagFollow::whereProfileId($pid)->count() >= HashtagFollow::MAX_LIMIT,
            422,
            'You cannot follow more than '.HashtagFollow::MAX_LIMIT.' hashtags.'
        );

        HashtagFollow::create([
            'user_id' => $user->id,
            'profile_id' => $pid,
            'hashtag_id' => $hashtag->id,
        ]);
        HashtagService::follow($profile->id, $hashtag->id);

        return ['state' => 'created'];
    }

    public function getTags(Request $request)
    {
        return HashtagFollow::with('hashtag')->whereUserId(Auth::id())
            ->inRandomOrder()
            ->take(3)
            ->get()
            ->map(function ($follow, $k) {
                return $follow->hashtag->name;
            });
    }
}
