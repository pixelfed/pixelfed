<?php

namespace App\Http\Controllers\Api\BaseApi;

use App\Http\Controllers\AvatarController;
use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Models\Avatar;
use App\Services\AccountService;
use App\Services\StatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait Account
{
    public function verifyCredentials(Request $request): JsonResponse
    {
        abort_if(! $request->user(), 403);

        $user = $request->user();
        if ($user->status != null) {
            Auth::logout();
            abort(403);
        }
        $res = AccountService::get($user->profile_id);

        return response()->json($res);
    }

    public function accountLikes(Request $request): JsonResponse
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'page' => 'sometimes|int|min:1|max:20',
            'limit' => 'sometimes|int|min:1|max:10',
        ]);

        $user = $request->user();
        $limit = $request->input('limit', 10);

        $res = DB::table('likes')
            ->whereProfileId($user->profile_id)
            ->latest()
            ->simplePaginate($limit)
            ->map(function ($id) use ($user) {
                $status = StatusService::get($id->status_id, false);
                $status['favourited'] = true;
                $status['reblogged'] = (bool) StatusService::isShared($id->status_id, $user->profile_id);

                return $status;
            })
            ->filter(function ($post) {
                return $post && isset($post['account']);
            })
            ->values();

        return response()->json($res);
    }

    public function avatarUpdate(Request $request): JsonResponse
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'upload' => 'required|mimetypes:image/jpeg,image/jpg,image/png|max:'.config('pixelfed.max_avatar_size'),
        ]);

        try {
            $user = $request->user();
            $profile = $user->profile;
            $file = $request->file('upload');
            $path = (new AvatarController)->getPath($user, $file);
            $dir = $path['root'];
            $name = $path['name'];
            $public = $path['storage'];
            $currentAvatar = storage_path('app/'.$profile->avatar->media_path);
            $loc = $request->file('upload')->storePubliclyAs($public, $name);

            $avatar = Avatar::whereProfileId($profile->id)->firstOrFail();
            $opath = $avatar->media_path;
            $avatar->media_path = "$public/$name";
            $avatar->change_count = ++$avatar->change_count;
            $avatar->last_processed_at = null;
            $avatar->save();

            Cache::forget("avatar:{$profile->id}");
            AvatarOptimize::dispatch($user->profile, $currentAvatar);
        } catch (\Exception $e) {
        }

        return response()->json([
            'code' => 200,
            'msg' => 'Avatar successfully updated',
        ]);
    }
}
