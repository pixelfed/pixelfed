<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\{
    Controller,
    AvatarController
};
use Auth, Cache, Storage, URL;
use Carbon\Carbon;
use App\{
    Avatar,
    Like,
    Media,
    Notification,
    Profile,
    Status,
    StatusArchived
};
use App\Transformer\Api\{
    AccountTransformer,
    NotificationTransformer,
    MediaTransformer,
    MediaDraftTransformer,
    StatusTransformer,
    StatusStatelessTransformer
};
use League\Fractal;
use App\Util\Media\Filter;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\VideoPipeline\{
    VideoOptimize,
    VideoPostProcess,
    VideoThumbnail
};
use App\Services\AccountService;
use App\Services\NotificationService;
use App\Services\MediaPathService;
use App\Services\MediaBlocklistService;
use App\Services\StatusService;

class BaseApiController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        // $this->middleware('auth');
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    public function notifications(Request $request)
    {
        abort_if(!$request->user(), 403);

		$pid = $request->user()->profile_id;
		$limit = $request->input('limit', 20);

		$since = $request->input('since_id');
		$min = $request->input('min_id');
		$max = $request->input('max_id');

		if(!$since && !$min && !$max) {
			$min = 1;
		}

		$maxId = null;
		$minId = null;

		if($max) {
			$res = NotificationService::getMax($pid, $max, $limit);
			$ids = NotificationService::getRankedMaxId($pid, $max, $limit);
			if(!empty($ids)) {
				$maxId = max($ids);
				$minId = min($ids);
			}
		} else {
			$res = NotificationService::getMin($pid, $min ?? $since, $limit);
			$ids = NotificationService::getRankedMinId($pid, $min ?? $since, $limit);
			if(!empty($ids)) {
				$maxId = max($ids);
				$minId = min($ids);
			}
		}

        if(empty($res) && !Cache::has('pf:services:notifications:hasSynced:'.$pid)) {
        	Cache::put('pf:services:notifications:hasSynced:'.$pid, 1, 1209600);
        	NotificationService::warmCache($pid, 100, true);
        }

        return response()->json($res);
    }

    public function accounts(Request $request, $id)
    {
        abort_if(!$request->user(), 403);
        $profile = Profile::findOrFail($id);
        $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function accountFollowers(Request $request, $id)
    {
        abort_if(!$request->user(), 403);
        $profile = Profile::findOrFail($id);
        $followers = $profile->followers;
        $resource = new Fractal\Resource\Collection($followers, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function accountFollowing(Request $request, $id)
    {
        abort_if(!$request->user(), 403);
        $profile = Profile::findOrFail($id);
        $following = $profile->following;
        $resource = new Fractal\Resource\Collection($following, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function accountStatuses(Request $request, $id)
    {
        abort_if(!$request->user(), 403);
        $this->validate($request, [
            'only_media' => 'nullable',
            'pinned' => 'nullable',
            'exclude_replies' => 'nullable',
            'max_id' => 'nullable|integer|min:1',
            'since_id' => 'nullable|integer|min:1',
            'min_id' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:24'
        ]);
        $limit = $request->limit ?? 20;
        $max_id = $request->max_id ?? false;
        $min_id = $request->min_id ?? false;
        $since_id = $request->since_id ?? false;
        $only_media = $request->only_media ?? false;
        $user = Auth::user();
        $account = Profile::whereNull('status')->findOrFail($id);
        $statuses = $account->statuses()->getQuery(); 
        if($only_media == true) {
            $statuses = $statuses
                ->whereIn('scope', ['public','unlisted'])
                ->whereHas('media')
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id');
        }
        if($id == $account->id && !$max_id && !$min_id && !$since_id) {
            $statuses = $statuses->orderBy('id', 'desc')
                ->paginate($limit);
        } else if($since_id) {
            $statuses = $statuses->where('id', '>', $since_id)
                ->orderBy('id', 'DESC')
                ->paginate($limit);
        } else if($min_id) {
            $statuses = $statuses->where('id', '>', $min_id)
                ->orderBy('id', 'ASC')
                ->paginate($limit);
        } else if($max_id) {
            $statuses = $statuses->where('id', '<', $max_id)
                ->orderBy('id', 'DESC')
                ->paginate($limit);
        } else {
            $statuses = $statuses->whereScope('public')->orderBy('id', 'desc')->paginate($limit);
        }
        $resource = new Fractal\Resource\Collection($statuses, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function avatarUpdate(Request $request)
    {
        abort_if(!$request->user(), 403);
        $this->validate($request, [
            'upload'   => 'required|mimetypes:image/jpeg,image/jpg,image/png|max:'.config('pixelfed.max_avatar_size'),
        ]);

        try {
            $user = Auth::user();
            $profile = $user->profile;
            $file = $request->file('upload');
            $path = (new AvatarController())->getPath($user, $file);
            $dir = $path['root'];
            $name = $path['name'];
            $public = $path['storage'];
            $currentAvatar = storage_path('app/'.$profile->avatar->media_path);
            $loc = $request->file('upload')->storeAs($public, $name);

            $avatar = Avatar::whereProfileId($profile->id)->firstOrFail();
            $opath = $avatar->media_path;
            $avatar->media_path = "$public/$name";
            $avatar->change_count = ++$avatar->change_count;
            $avatar->last_processed_at = null;
            $avatar->save();

            Cache::forget("avatar:{$profile->id}");
            AvatarOptimize::dispatch($user->profile, $currentAvatar);
        } catch (Exception $e) {
        }

        return response()->json([
            'code' => 200,
            'msg'  => 'Avatar successfully updated',
        ]);
    }

    public function showTempMedia(Request $request, $profileId, $mediaId, $timestamp)
    {
        abort(400, 'Endpoint deprecated');
    }

    public function uploadMedia(Request $request)
    {
        abort(400, 'Endpoint deprecated');
    }

    public function deleteMedia(Request $request)
    {
        abort(400, 'Endpoint deprecated');
    }

    public function verifyCredentials(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);
        if($user->status != null) {
            Auth::logout();
            abort(403);
        }
        $res = AccountService::get($user->profile_id);
        return response()->json($res);
    }

    public function drafts(Request $request)
    {
        $user = $request->user();
        abort_if(!$request->user(), 403);

        $medias = Media::whereUserId($user->id)
            ->whereNull('status_id')
            ->latest()
            ->take(13)
            ->get();
        $resource = new Fractal\Resource\Collection($medias, new MediaDraftTransformer());
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function accountLikes(Request $request)
    {
        abort_if(!$request->user(), 403);
        $this->validate($request, [
        	'page' => 'sometimes|int|min:1|max:20',
        	'limit' => 'sometimes|int|min:1|max:10'
        ]);

        $user = $request->user();
        $limit = $request->input('limit', 10);

        $res = \DB::table('likes')
        	->whereProfileId($user->profile_id)
        	->latest()
        	->simplePaginate($limit)
        	->map(function($id) {
        		$status = StatusService::get($id->status_id, false);
        		$status['favourited'] = true;
        		return $status;
        	})
        	->filter(function($post) {
        		return $post && isset($post['account']);
        	})
        	->values();
        return response()->json($res);
    }

    public function archive(Request $request, $id)
    {
        abort_if(!$request->user(), 403);

        $status = Status::whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereProfileId($request->user()->profile_id)
            ->findOrFail($id);

        if($status->scope === 'archived') {
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

    public function unarchive(Request $request, $id)
    {
        abort_if(!$request->user(), 403);

        $status = Status::whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereProfileId($request->user()->profile_id)
            ->findOrFail($id);

        if($status->scope !== 'archived') {
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

    public function archivedPosts(Request $request)
    {
        abort_if(!$request->user(), 403);

        $statuses = Status::whereProfileId($request->user()->profile_id)
            ->whereScope('archived')
            ->orderByDesc('id')
            ->simplePaginate(10);

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Collection($statuses, new StatusStatelessTransformer());
        return $fractal->createData($resource)->toArray();
    }
}
