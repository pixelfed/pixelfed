<?php

namespace App\Http\Controllers;

use App\Jobs\FollowPipeline\FollowAcceptPipeline;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Jobs\FollowPipeline\FollowRejectPipeline;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\UserFilter;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\NotificationService;
use App\Services\RelationshipService;
use App\Services\UserFilterService;
use App\Transformer\Api\Mastodon\v1\AccountTransformer;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class AccountController extends Controller
{
    protected $filters = [
        'user.mute',
        'user.block',
    ];

    const FILTER_LIMIT_MUTE_TEXT = 'You cannot mute more than ';

    const FILTER_LIMIT_BLOCK_TEXT = 'You cannot block more than ';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function notifications(Request $request): View
    {
        return view('account.activity');
    }

    public function followingActivity(Request $request): View
    {
        $this->validate($request, [
            'page' => 'nullable|min:1|max:3',
            'a' => 'nullable|alpha_dash',
        ]);

        $action = $request->input('a');
        $allowed = ['like', 'follow'];
        $timeago = Carbon::now()->subMonths(3);

        $profile = $request->user()->profile;
        $following = $profile->following->pluck('id');

        $notifications = Notification::whereIn('actor_id', $following)
            ->whereIn('action', $allowed)
            ->where('actor_id', '<>', $profile->id)
            ->where('profile_id', '<>', $profile->id)
            ->whereDate('created_at', '>', $timeago)
            ->orderBy('notifications.created_at', 'desc')
            ->simplePaginate(30);

        return view('account.following', compact('profile', 'notifications'));
    }

    public function direct(): View
    {
        return view('account.direct');
    }

    public function directMessage(Request $request, $id): View
    {
        $profile = Profile::where('id', '!=', $request->user()->profile_id)
            ->findOrFail($id);

        return view('account.directmessage', compact('id'));
    }

    public function mute(Request $request): JsonResponse|RedirectResponse
    {
        $this->validate($request, [
            'type' => 'required|string|in:user',
            'item' => 'required|integer|min:1',
        ]);

        $pid = $request->user()->profile_id;
        $count = UserFilterService::muteCount($pid);
        $maxLimit = (int) config_cache('instance.user_filters.max_user_mutes');
        abort_if($count >= $maxLimit, 422, self::FILTER_LIMIT_MUTE_TEXT.$maxLimit.' accounts');
        if ($count == 0) {
            $filterCount = UserFilter::whereUserId($pid)->count();
            abort_if($filterCount >= $maxLimit, 422, self::FILTER_LIMIT_MUTE_TEXT.$maxLimit.' accounts');
        }
        $type = $request->input('type');
        $item = $request->input('item');
        $action = $type.'.mute';

        if (! in_array($action, $this->filters)) {
            return abort(406);
        }
        $filterable = [];
        $profile = null;

        switch ($type) {
            case 'user':
                $profile = Profile::findOrFail($item);
                if ($profile->id == $pid) {
                    return abort(403);
                }
                $class = get_class($profile);
                $filterable['id'] = $profile->id;
                $filterable['type'] = $class;
                break;
        }

        $filter = UserFilter::firstOrCreate([
            'user_id' => $pid,
            'filterable_id' => $filterable['id'],
            'filterable_type' => $filterable['type'],
            'filter_type' => 'mute',
        ]);

        UserFilterService::mute($pid, $filterable['id']);
        $res = RelationshipService::refresh($pid, $profile->id);

        if ($request->wantsJson()) {
            return response()->json($res);
        } else {
            return redirect()->back();
        }
    }

    public function unmute(Request $request): JsonResponse|RedirectResponse
    {
        $this->validate($request, [
            'type' => 'required|string|in:user',
            'item' => 'required|integer|min:1',
        ]);

        $pid = $request->user()->profile_id;
        $type = $request->input('type');
        $item = $request->input('item');
        $action = $type.'.mute';

        if (! in_array($action, $this->filters)) {
            return abort(406);
        }
        $filterable = [];
        switch ($type) {
            case 'user':
                $profile = Profile::findOrFail($item);
                if ($profile->id == $pid) {
                    return abort(403);
                }
                $class = get_class($profile);
                $filterable['id'] = $profile->id;
                $filterable['type'] = $class;
                break;

            default:
                abort(400);
                break;
        }

        $filter = UserFilter::whereUserId($pid)
            ->whereFilterableId($filterable['id'])
            ->whereFilterableType($filterable['type'])
            ->whereFilterType('mute')
            ->first();

        if ($filter) {
            UserFilterService::unmute($pid, $filterable['id']);
            $filter->delete();
        }

        $res = RelationshipService::refresh($pid, $profile->id);

        if ($request->wantsJson()) {
            return response()->json($res);
        } else {
            return redirect()->back();
        }
    }

    public function block(Request $request): JsonResponse|RedirectResponse
    {
        $this->validate($request, [
            'type' => 'required|string|in:user',
            'item' => 'required|integer|min:1',
        ]);
        $pid = $request->user()->profile_id;
        $count = UserFilterService::blockCount($pid);
        $maxLimit = (int) config_cache('instance.user_filters.max_user_blocks');
        abort_if($count >= $maxLimit, 422, self::FILTER_LIMIT_BLOCK_TEXT.$maxLimit.' accounts');
        if ($count == 0) {
            $filterCount = UserFilter::whereUserId($pid)->whereFilterType('block')->count();
            abort_if($filterCount >= $maxLimit, 422, self::FILTER_LIMIT_BLOCK_TEXT.$maxLimit.' accounts');
        }
        $type = $request->input('type');
        $item = $request->input('item');
        $action = $type.'.block';
        if (! in_array($action, $this->filters)) {
            return abort(406);
        }
        $filterable = [];
        $profile = null;

        switch ($type) {
            case 'user':
                $profile = Profile::findOrFail($item);
                if ($profile->id == $pid || ($profile->user && $profile->user->is_admin == true)) {
                    return abort(403);
                }
                $class = get_class($profile);
                $filterable['id'] = $profile->id;
                $filterable['type'] = $class;

                $followed = Follower::whereProfileId($profile->id)->whereFollowingId($pid)->first();
                if ($followed) {
                    $followed->delete();
                    $profile->following_count = Follower::whereProfileId($profile->id)->count();
                    $profile->save();
                    $selfProfile = $request->user()->profile;
                    $selfProfile->followers_count = Follower::whereFollowingId($pid)->count();
                    $selfProfile->save();
                    FollowerService::remove($profile->id, $pid);
                    AccountService::del($pid);
                    AccountService::del($profile->id);
                }

                $following = Follower::whereProfileId($pid)->whereFollowingId($profile->id)->first();
                if ($following) {
                    $following->delete();
                    $profile->followers_count = Follower::whereFollowingId($profile->id)->count();
                    $profile->save();
                    $selfProfile = $request->user()->profile;
                    $selfProfile->following_count = Follower::whereProfileId($pid)->count();
                    $selfProfile->save();
                    FollowerService::remove($pid, $profile->id);
                    AccountService::del($pid);
                    AccountService::del($profile->id);
                }

                Notification::whereProfileId($pid)
                    ->whereActorId($profile->id)
                    ->get()
                    ->map(function ($n) use ($pid) {
                        NotificationService::del($pid, $n['id']);
                        $n->forceDelete();
                    });
                break;
        }

        $filter = UserFilter::firstOrCreate([
            'user_id' => $pid,
            'filterable_id' => $filterable['id'],
            'filterable_type' => $filterable['type'],
            'filter_type' => 'block',
        ]);

        UserFilterService::block($pid, $filterable['id']);
        $res = RelationshipService::refresh($pid, $profile->id);

        if ($request->wantsJson()) {
            return response()->json($res);
        } else {
            return redirect()->back();
        }
    }

    public function unblock(Request $request): JsonResponse|RedirectResponse
    {
        $this->validate($request, [
            'type' => 'required|string|in:user',
            'item' => 'required|integer|min:1',
        ]);

        $pid = $request->user()->profile_id;
        $type = $request->input('type');
        $item = $request->input('item');
        $action = $type.'.block';
        if (! in_array($action, $this->filters)) {
            return abort(406);
        }
        $filterable = [];
        switch ($type) {
            case 'user':
                $profile = Profile::findOrFail($item);
                if ($profile->id == $pid) {
                    return abort(403);
                }
                $class = get_class($profile);
                $filterable['id'] = $profile->id;
                $filterable['type'] = $class;
                break;

            default:
                abort(400);
                break;
        }

        $filter = UserFilter::whereUserId($pid)
            ->whereFilterableId($filterable['id'])
            ->whereFilterableType($filterable['type'])
            ->whereFilterType('block')
            ->first();

        if ($filter) {
            $filter->delete();
            UserFilterService::unblock($pid, $filterable['id']);
        }

        $res = RelationshipService::refresh($pid, $profile->id);

        if ($request->wantsJson()) {
            return response()->json($res);
        } else {
            return redirect()->back();
        }
    }

    public function followRequests(Request $request): View
    {
        $pid = $request->user()->profile->id;
        $followers = FollowRequest::whereFollowingId($pid)->orderBy('id', 'desc')->whereIsRejected(0)->simplePaginate(10);

        return view('account.follow-requests', compact('followers'));
    }

    public function followRequestsJson(Request $request): JsonResponse
    {
        $pid = $request->user()->profile_id;
        $followers = FollowRequest::whereFollowingId($pid)->orderBy('id', 'desc')->whereIsRejected(0)->get();
        $res = [
            'count' => $followers->count(),
            'accounts' => $followers->take(10)->map(function ($a) {
                $actor = $a->actor;

                return [
                    'rid' => (string) $a->id,
                    'id' => (string) $actor->id,
                    'username' => $actor->username,
                    'avatar' => $actor->avatarUrl(),
                    'url' => $actor->url(),
                    'local' => $actor->domain == null,
                    'account' => AccountService::get($actor->id),
                ];
            }),
        ];

        return response()->json($res, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function followRequestHandle(Request $request): JsonResponse
    {
        $this->validate($request, [
            'action' => 'required|string|max:10',
            'id' => 'required|integer|min:1',
        ]);

        $pid = $request->user()->profile->id;
        $action = $request->input('action') === 'accept' ? 'accept' : 'reject';
        $id = $request->input('id');
        $followRequest = FollowRequest::whereFollowingId($pid)->findOrFail($id);
        $follower = $followRequest->follower;

        switch ($action) {
            case 'accept':
                $follow = new Follower;
                $follow->profile_id = $follower->id;
                $follow->following_id = $pid;
                $follow->save();

                $profile = Profile::findOrFail($pid);
                $profile->followers_count++;
                $profile->save();
                AccountService::del($profile->id);

                $profile = Profile::findOrFail($follower->id);
                $profile->following_count++;
                $profile->save();
                AccountService::del($profile->id);

                if ($follower->domain != null && $follower->private_key === null) {
                    FollowAcceptPipeline::dispatch($followRequest)->onQueue('follow');
                } else {
                    FollowPipeline::dispatch($follow);
                    $followRequest->delete();
                }
                break;

            case 'reject':
                if ($follower->domain != null && $follower->private_key === null) {
                    FollowRejectPipeline::dispatch($followRequest)->onQueue('follow');
                } else {
                    $followRequest->delete();
                }
                break;
        }

        Cache::forget('profile:follower_count:'.$pid);
        Cache::forget('profile:following_count:'.$pid);
        RelationshipService::refresh($pid, $follower->id);

        return response()->json(['msg' => 'success'], 200);
    }

    public function confirmPassword(Request $request): View
    {
        return view('auth.sudo');
    }

    public function confirmPasswordStore(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'password' => 'required|string|max:500',
        ]);

        if (! Hash::check($request->password, $request->user()->password)) {
            return redirect()
                ->back()
                ->withErrors(['password' => __('auth.failed')]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended();
    }

    public function accountRestored(Request $request): void {}

    public function accountMutes(Request $request): JsonResponse
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 40;

        $mutes = UserFilter::whereUserId($user->profile_id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('mute')
            ->simplePaginate($limit)
            ->pluck('filterable_id');

        $accounts = Profile::find($mutes);
        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Collection($accounts, new AccountTransformer);
        $res = $fractal->createData($resource)->toArray();
        $url = $request->url();
        $page = $request->input('page', 1);
        $next = $page < 40 ? $page + 1 : 40;
        $prev = $page > 1 ? $page - 1 : 1;
        $links = '<'.$url.'?page='.$next.'&limit='.$limit.'>; rel="next", <'.$url.'?page='.$prev.'&limit='.$limit.'>; rel="prev"';

        return response()->json($res, 200, ['Link' => $links]);
    }

    public function accountBlocks(Request $request): JsonResponse
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
            'page' => 'nullable|integer|min:1|max:10',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 40;

        $blocked = UserFilter::select('filterable_id', 'filterable_type', 'filter_type', 'user_id')
            ->whereUserId($user->profile_id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->simplePaginate($limit)
            ->pluck('filterable_id');

        $profiles = Profile::findOrFail($blocked);
        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Collection($profiles, new AccountTransformer);
        $res = $fractal->createData($resource)->toArray();
        $url = $request->url();
        $page = $request->input('page', 1);
        $next = $page < 40 ? $page + 1 : 40;
        $prev = $page > 1 ? $page - 1 : 1;
        $links = '<'.$url.'?page='.$next.'&limit='.$limit.'>; rel="next", <'.$url.'?page='.$prev.'&limit='.$limit.'>; rel="prev"';

        return response()->json($res, 200, ['Link' => $links]);
    }

    public function accountBlocksV2(Request $request): JsonResponse
    {
        return response()->json(UserFilterService::blocks($request->user()->profile_id), 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function accountMutesV2(Request $request): JsonResponse
    {
        return response()->json(UserFilterService::mutes($request->user()->profile_id), 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function accountFiltersV2(Request $request): JsonResponse
    {
        return response()->json(UserFilterService::filters($request->user()->profile_id), 200, [], JSON_UNESCAPED_SLASHES);
    }
}
