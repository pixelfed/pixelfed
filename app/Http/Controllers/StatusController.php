<?php

namespace App\Http\Controllers;

use App\Jobs\SharePipeline\SharePipeline;
use App\Jobs\SharePipeline\UndoSharePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\AccountInterstitial;
use App\Models\Profile;
use App\Models\Status;
use App\Models\StatusView;
use App\Services\AccountService;
use App\Services\HashidService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Transformer\ActivityPub\Verb\Note;
use App\Transformer\ActivityPub\Verb\Question;
use App\Util\Media\License;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use League\Fractal;

class StatusController extends Controller
{
    public function show(Request $request, string $username, string $id): View|JsonResponse|RedirectResponse
    {
        if ($request->user() && $request->input('fs') !== '1') {
            return redirect('/i/web/post/'.$id);
        }

        $statusData = StatusService::get($id, false);

        abort_if(
            ! $statusData ||
                ! isset($statusData['account']['username']) ||
                $statusData['account']['username'] !== $username ||
                isset($statusData['reblog']),
            404
        );

        $user = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();

        if ($user->status !== null) {
            return ProfileController::accountCheck($user);
        }

        $status = Status::whereProfileId($user->id)
            ->whereNull('reblog_of_id')
            ->whereIn('scope', ['public', 'unlisted', 'private'])
            ->findOrFail($id);

        $this->guardStatusVisibility($request, $user, $status);

        if ($request->wantsJson() && (bool) config_cache('federation.activitypub.enabled')) {
            return $this->showActivityPub($request, $status);
        }

        $template = $status->in_reply_to_id ? 'status.reply' : 'status.show';

        return view($template, compact('user', 'status'));
    }

    public function shortcodeRedirect(Request $request, string $id): RedirectResponse
    {
        $hid = HashidService::decode($id);
        abort_if(! $hid, 404);

        return redirect('/i/web/post/'.$hid);
    }

    public function showEmbed(Request $request, string $username, string $id): Response
    {
        if (! (bool) config_cache('instance.embed.post')) {
            return $this->embedRemoved();
        }

        $status = StatusService::get($id);

        if (
            ! $status ||
            ! isset($status['account']['id'], $status['account']['username'], $status['local']) ||
            ! $status['local'] ||
            strtolower($status['account']['username']) !== strtolower($username) ||
            isset($status['account']['moved']['id'])
        ) {
            return $this->embedRemoved(404);
        }

        $profile = AccountService::get($status['account']['id'], true);

        if (
            ! $profile ||
            $profile['locked'] ||
            ! $profile['local'] ||
            ! AccountService::canEmbed($profile['id'])
        ) {
            return $this->embedRemoved();
        }

        if ($this->profileFlaggedAsSpam($profile['id'])) {
            return $this->embedRemoved();
        }

        if (
            intval($status['account']['id']) !== intval($profile['id']) ||
            $status['sensitive'] ||
            $status['visibility'] !== 'public' ||
            ! in_array($status['pf_type'], ['photo', 'photo:album'])
        ) {
            return $this->embedRemoved();
        }

        $showLikes = $request->boolean('likes');
        $showCaption = $request->boolean('caption');
        $layout = $request->input('layout') === 'compact' ? 'compact' : 'full';

        return response(view('status.embed', compact('status', 'showLikes', 'showCaption', 'layout')))
            ->header('X-Frame-Options', 'ALLOWALL');
    }

    public function showObject(Request $request, string $username, string $id): View|JsonResponse
    {
        abort_unless((bool) config_cache('federation.activitypub.enabled'), 404);

        $user = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();

        if ($user->status !== null) {
            return ProfileController::accountCheck($user);
        }

        $status = Status::whereProfileId($user->id)
            ->whereIn('scope', ['public', 'unlisted', 'private'])
            ->findOrFail($id);

        abort_if($status->uri, 404);

        $this->guardStatusVisibility($request, $user, $status);

        return $this->showActivityPub($request, $status);
    }

    public function compose(): View
    {
        $this->authCheck();

        return view('status.compose');
    }

    public function delete(Request $request): JsonResponse|RedirectResponse
    {
        $this->authCheck();

        $this->validate($request, [
            'item' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $pid = $user->profile_id;

        $status = Status::whereNull('reblog_of_id')->findOrFail($request->input('item'));

        $isOwner = $status->profile_id == $pid;
        $isParentOwner = false;

        if ($status->in_reply_to_id) {
            $parent = Status::find($status->in_reply_to_id);
            $isParentOwner = $parent && $parent->profile_id == $pid;
        }

        abort_unless($isOwner || $isParentOwner || $user->is_admin, 403);

        if ($user->is_admin && ! $isOwner && $status->uri === null) {
            AccountInterstitial::createFromStatus($status, 'post.removed', 'account.moderation.post.removed');
        }

        Cache::forget('_api:statuses:recent_9:'.$status->profile_id);
        Cache::forget('profile:status_count:'.$status->profile_id);
        Cache::forget('profile:embed:'.$status->profile_id);
        StatusService::del($status->id, true);

        $status->uri ? RemoteStatusDelete::dispatch($status) : StatusDelete::dispatch($status);

        if ($request->wantsJson()) {
            return response()->json(['Status successfully deleted.']);
        }

        return redirect($user->url());
    }

    public function storeShare(Request $request): JsonResponse|RedirectResponse
    {
        $this->authCheck();

        $this->validate($request, [
            'item' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $profile = $user->profile;

        $status = Status::whereScope('public')->findOrFail($request->input('item'));

        $statusAccount = AccountService::get($status->profile_id);
        abort_if(! $statusAccount || isset($statusAccount['moved']['id']), 422, 'Account moved');

        $count = $status->reblogs_count;

        $shares = Status::whereProfileId($profile->id)
            ->whereReblogOfId($status->id)
            ->get();

        if ($shares->isNotEmpty()) {
            foreach ($shares as $share) {
                UndoSharePipeline::dispatch($share);
                $count--;
            }
            ReblogService::del($profile->id, $status->id);
        } else {
            $defaultCaption = config_cache('database.default') === 'mysql' ? null : '';

            $share = new Status;
            $share->caption = $defaultCaption;
            $share->rendered = $defaultCaption;
            $share->profile_id = $profile->id;
            $share->reblog_of_id = $status->id;
            $share->in_reply_to_profile_id = $status->profile_id;
            $share->type = 'share';
            $share->save();
            $count++;

            SharePipeline::dispatch($share);
            ReblogService::add($profile->id, $status->id);
        }

        Cache::forget('status:'.$status->id.':sharedby:userid:'.$user->id);
        StatusService::del($status->id);

        if ($request->ajax()) {
            return response()->json(['code' => 200, 'msg' => 'Share saved', 'count' => $count]);
        }

        return redirect($status->url());
    }

    public function showActivityPub(Request $request, Status|array $status): JsonResponse
    {
        $id = $status instanceof Status ? $status->id : $status['id'];

        return response()->json(
            $this->activityPubObject($id),
            200,
            ['Content-Type' => 'application/activity+json'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public function edit(Request $request, string $username, string $id): View
    {
        $this->authCheck();

        $user = $request->user()->profile;
        $status = Status::whereProfileId($user->id)
            ->with('media')
            ->findOrFail($id);
        $licenses = License::get();

        return view('status.edit', compact('user', 'status', 'licenses'));
    }

    public function editStore(Request $request, string $username, string $id): RedirectResponse
    {
        $this->authCheck();

        $this->validate($request, [
            'license' => 'nullable|integer|min:1|max:16',
        ]);

        $status = Status::whereProfileId($request->user()->profile_id)
            ->with('media')
            ->findOrFail($id);

        $licenseId = $request->input('license');

        $status->media->each(function ($media) use ($licenseId) {
            $media->license = $licenseId;
            $media->save();
        });

        Cache::forget('status:transformer:media:attachments:'.$status->id);
        StatusService::del($status->id);

        return redirect($status->url());
    }

    public function toggleVisibility(Request $request): JsonResponse
    {
        $this->authCheck();

        $this->validate($request, [
            'item' => 'required|string|min:1|max:20',
            'disableComments' => 'required|boolean',
        ]);

        $user = $request->user();
        $status = Status::findOrFail($request->input('item'));

        abort_if($status->profile_id != $user->profile_id && ! $user->is_admin, 403);

        $status->comments_disabled = ! $status->comments_disabled;
        $status->save();

        StatusService::del($status->id);

        return response()->json([200]);
    }

    public function storeView(Request $request): JsonResponse
    {
        $this->authCheck();

        $views = $request->input('_v');

        if (empty($views) || ! is_array($views)) {
            return response()->json(0);
        }

        $pid = $request->user()->profile_id;

        Cache::forget('profile:home-timeline-cursor:'.$request->user()->id);

        $rows = collect($views)
            ->filter(fn ($view) => is_array($view)
                && isset($view['sid'], $view['pid'])
                && is_numeric($view['sid'])
                && is_numeric($view['pid']))
            ->map(fn ($view) => [
                'status_id' => (int) $view['sid'],
                'status_profile_id' => (int) $view['pid'],
                'profile_id' => $pid,
            ])
            ->unique('status_id')
            ->take(100)
            ->values();

        if ($rows->isEmpty()) {
            return response()->json(0);
        }

        $seen = StatusView::whereProfileId($pid)
            ->whereIn('status_id', $rows->pluck('status_id'))
            ->pluck('status_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $rows->reject(fn ($row) => in_array($row['status_id'], $seen, true))
            ->each(fn ($row) => StatusView::create($row));

        return response()->json(1);
    }

    public static function mimeTypeCheck($mimes): string
    {
        $allowed = explode(',', (string) config_cache('pixelfed.media_types'));

        if (! in_array('image/jpg', $allowed)) {
            $allowed[] = 'image/jpg';
        }

        $photos = 0;
        $videos = 0;

        foreach ($mimes as $mime) {
            if (! in_array($mime, $allowed) && $mime !== 'video/mp4') {
                continue;
            }
            if (str_starts_with($mime, 'image/')) {
                $photos++;
            }
            if (str_starts_with($mime, 'video/')) {
                $videos++;
            }
        }

        return match (true) {
            $photos === 1 && $videos === 0 => 'photo',
            $videos === 1 && $photos === 0 => 'video',
            $photos > 1 && $videos === 0 => 'photo:album',
            $videos > 1 && $photos === 0 => 'video:album',
            $photos >= 1 && $videos >= 1 => 'photo:video:album',
            default => 'text',
        };
    }

    protected function guardStatusVisibility(Request $request, Profile $owner, Status $status): void
    {
        $viewer = $request->user();

        if ($status->scope === 'private' || $owner->is_private) {
            abort_if(! $viewer, 404);

            $viewerProfile = $viewer->profile;

            $isOwner = $viewerProfile->id === $owner->id;
            $isAdmin = (bool) $viewer->is_admin;
            $isFollower = $owner->followedBy($viewerProfile);

            abort_if(! $isOwner && ! $isAdmin && ! $isFollower, 404);
        }

        if ($status->type === 'archived') {
            abort_if(! $viewer || $viewer->profile_id !== $status->profile_id, 404);
        }
    }

    protected function activityPubObject(int|string $id): array
    {
        $key = 'pf:status:ap:v1:sid:'.$id;
        $cached = Cache::get($key);

        if (is_array($cached)) {
            return $cached;
        }

        $status = Status::findOrFail($id);
        $object = $status->type === 'poll' ? new Question : new Note;
        $fractal = new Fractal\Manager;
        $resource = new Fractal\Resource\Item($status, $object);
        $data = $fractal->createData($resource)->toArray()['data'];

        Cache::put($key, $data, 3600);

        return $data;
    }

    protected function profileFlaggedAsSpam(int|string $profileId): bool
    {
        return (bool) Cache::remember('profile:ai-check:spam-login:'.$profileId, 3600, function () use ($profileId) {
            $profile = Profile::find($profileId);

            if (! $profile) {
                return true;
            }

            return AccountInterstitial::whereUserId($profile->user_id)
                ->where('is_spam', 1)
                ->exists();
        });
    }

    protected function embedRemoved(int $code = 200): Response
    {
        return response(view('status.embed-removed'), $code)
            ->header('X-Frame-Options', 'ALLOWALL');
    }

    protected function authCheck(): void
    {
        abort_if(! request()->user(), 403);
    }
}
