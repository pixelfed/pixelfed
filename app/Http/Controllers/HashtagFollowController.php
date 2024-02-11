<?php

namespace App\Http\Controllers;

use App\Hashtag;
use App\HashtagFollow;
use App\Services\HashtagService;
use Auth;
use Illuminate\Http\Request;

class HashtagFollowController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|alpha_num|min:1|max:124|exists:hashtags,name',
        ]);

        $user = Auth::user();
        $profile = $user->profile;
        $tag = $request->input('name');

        $hashtag = Hashtag::whereName($tag)->firstOrFail();

        $hashtagFollow = HashtagFollow::firstOrCreate([
            'user_id' => $user->id,
            'profile_id' => $user->profile_id ?? $user->profile->id,
            'hashtag_id' => $hashtag->id,
        ]);

        if ($hashtagFollow->wasRecentlyCreated) {
            $state = 'created';
            HashtagService::follow($profile->id, $hashtag->id);
            // todo: send to HashtagFollowService
        } else {
            $state = 'deleted';
            HashtagService::unfollow($profile->id, $hashtag->id);
            $hashtagFollow->delete();
        }

        return [
            'state' => $state,
        ];
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
