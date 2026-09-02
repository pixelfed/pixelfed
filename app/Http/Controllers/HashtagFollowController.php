<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\HashtagFollow;
use App\Services\HashtagService;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

#[Middleware('auth')]
class HashtagFollowController extends Controller
{
    public function store(Request $request): array
    {
        $this->validate($request, [
            'name' => 'required|alpha_num|min:1|max:124|exists:hashtags,name',
        ]);

        $user = $request->user();
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
