<?php

namespace App\Http\Controllers;

use App\Jobs\LikePipeline\LikePipeline;
use App\Jobs\LikePipeline\UnlikePipeline;
use App\Like;
use App\Services\StatusService;
use App\Status;
use Auth;
use Cache;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'item' => 'required|integer|min:1',
        ]);

        abort(422, 'Deprecated API Endpoint');

        $user = Auth::user();
        $profile = $user->profile;
        $status = Status::findOrFail($request->input('item'));

        if (Like::whereStatusId($status->id)->whereProfileId($profile->id)->exists()) {
            $like = Like::whereProfileId($profile->id)->whereStatusId($status->id)->firstOrFail();
            UnlikePipeline::dispatch($like)->onQueue('feed');
        } else {
            abort_if(
                Like::whereProfileId($user->profile_id)
                    ->where('created_at', '>', now()->subDay())
                    ->count() >= Like::MAX_PER_DAY,
                429
            );
            $count = $status->likes_count > 4 ? $status->likes_count : $status->likes()->count();
            $like = Like::firstOrCreate([
                'profile_id' => $user->profile_id,
                'status_id' => $status->id,
            ]);
            if ($like->wasRecentlyCreated == true) {
                $count++;
                $status->likes_count = $count;
                $like->status_profile_id = $status->profile_id;
                $like->is_comment = in_array($status->type, [
                    'photo',
                    'photo:album',
                    'video',
                    'video:album',
                    'photo:video:album',
                ]) == false;
                $like->save();
                $status->save();
                LikePipeline::dispatch($like)->onQueue('feed');
            }
        }

        Cache::forget('status:'.$status->id.':likedby:userid:'.$user->id);
        StatusService::refresh($status->id);

        if ($request->ajax()) {
            $response = ['code' => 200, 'msg' => 'Like saved', 'count' => 0];
        } else {
            $response = redirect($status->url());
        }

        return $response;
    }
}
