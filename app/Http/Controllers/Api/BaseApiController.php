<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\Controller;
use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Jobs\NotificationPipeline\NotificationWarmUserCache;
use App\Models\Avatar;
use App\Models\Status;
use App\Models\StatusArchived;
use App\Services\AccountService;
use App\Services\NotificationService;
use App\Services\StatusService;
use App\Transformer\Api\StatusStatelessTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class BaseApiController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        // $this->middleware('auth');
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function notifications(Request $request): JsonResponse
    {
        abort_if(! $request->user(), 403);

        $pid = $request->user()->profile_id;
        $limit = min((int) $request->input('limit', 20), 40);

        $since = $request->input('since_id');
        $min = $request->input('min_id');
        $max = $request->input('max_id');

        if (! $since && ! $min && ! $max) {
            $min = 1;
        }

        $page = $max
            ? NotificationService::getMaxPage($pid, $max, $limit)
            : NotificationService::getMinPage($pid, $min ?? $since, $limit);

        if (empty($page['data']) && ! Cache::has('pf:services:notifications:hasSynced:'.$pid)) {
            Cache::put('pf:services:notifications:hasSynced:'.$pid, 1, 1209600);
            NotificationWarmUserCache::dispatch($pid);
        }

        $res = collect($page['data'])
            ->filter(fn ($n) => isset($n['account']['id']))
            ->values();

        $headers = [];
        if ($page['next_max_id']) {
            $headers['Link'] = '<'.config('app.url').'/api/pixelfed/v1/notifications?limit='.$limit.'&max_id='.$page['next_max_id'].'>; rel="next"';
        }

        return response()->json($res, 200, $headers);
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
            Log::error('BaseApiController@avatarUpdate failed: '.$e->getMessage(), [
                'user_id' => $request->user()?->id,
                'exception' => $e,
            ]);

            return response()->json([
                'code' => 500,
                'msg' => 'There was an error updating your avatar. Please try again.',
            ], 500);
        }

        return response()->json([
            'code' => 200,
            'msg' => 'Avatar successfully updated',
        ]);
    }

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

    public function archive(Request $request, $id): array
    {
        abort_if(! $request->user(), 403);

        $status = Status::whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereProfileId($request->user()->profile_id)
            ->findOrFail($id);

        if ($status->scope === 'archived') {
            return [200];
        }

        $archive = new StatusArchived;
        $archive->status_id = $status->id;
        $archive->profile_id = $status->profile_id;
        $archive->original_scope = $status->scope;
        $archive->save();

        $status->scope = 'archived';
        $status->visibility = 'draft';
        $status->save();
        StatusService::del($status->id, true);
        AccountService::syncPostCount($status->profile_id);

        return [200];
    }

    public function unarchive(Request $request, $id): array
    {
        abort_if(! $request->user(), 403);

        $status = Status::whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereProfileId($request->user()->profile_id)
            ->findOrFail($id);

        if ($status->scope !== 'archived') {
            return [200];
        }

        $archive = StatusArchived::whereStatusId($status->id)
            ->whereProfileId($status->profile_id)
            ->firstOrFail();

        $status->scope = $archive->original_scope;
        $status->visibility = $archive->original_scope;
        $status->save();
        $archive->delete();
        StatusService::del($status->id, true);
        AccountService::syncPostCount($status->profile_id);

        return [200];
    }

    public function archivedPosts(Request $request): array
    {
        abort_if(! $request->user(), 403);

        $statuses = Status::whereProfileId($request->user()->profile_id)
            ->whereScope('archived')
            ->orderByDesc('id')
            ->simplePaginate(10);

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Collection($statuses, new StatusStatelessTransformer);

        return $fractal->createData($resource)->toArray();
    }
}
