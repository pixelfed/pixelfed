<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Portfolio;
use Cache;
use DB;
use App\Status;
use App\User;
use App\Services\AccountService;
use App\Services\StatusService;

class PortfolioController extends Controller
{
    public function index(Request $request)
    {
        return view('portfolio.index');
    }

    public function show(Request $request, $username)
    {
        $user = User::whereUsername($username)->first();

        if(!$user) {
            return view('portfolio.404');
        }

        $portfolio = Portfolio::whereUserId($user->id)->firstOrFail();
        $user = AccountService::get($user->profile_id);

        if($user['locked']) {
            return view('portfolio.404');
        }

        if($portfolio->active != true) {
            if(!$request->user()) {
                return view('portfolio.404');
            }

            if($request->user()->profile_id == $user['id']) {
                return redirect(config('portfolio.path') . '/settings');
            }

            return view('portfolio.404');
        }

        return view('portfolio.show', compact('user', 'portfolio'));
    }

    public function showPost(Request $request, $username, $id)
    {
        $authed = $request->user();
        $post = StatusService::get($id);

        if(!$post) {
            return view('portfolio.404');
        }

        $user = AccountService::get($post['account']['id']);
        $portfolio = Portfolio::whereProfileId($user['id'])->first();

        if($user['locked'] || $portfolio->active != true) {
            return view('portfolio.404');
        }

        if(!$post || $post['visibility'] != 'public' || $post['pf_type'] != 'photo' || $user['id'] != $post['account']['id']) {
            return view('portfolio.404');
        }

        return view('portfolio.show_post', compact('user', 'post', 'authed'));
    }

    public function myRedirect(Request $request)
    {
        abort_if(!$request->user(), 404);

        $user = $request->user();

        if(Portfolio::whereProfileId($user->profile_id)->exists() === false) {
            $portfolio = new Portfolio;
            $portfolio->profile_id = $user->profile_id;
            $portfolio->user_id = $user->id;
            $portfolio->active = false;
            $portfolio->save();
        }

        $domain = config('portfolio.domain');
        $path = config('portfolio.path');
        $url = 'https://' . $domain . $path;

        return redirect($url);
    }

    public function settings(Request $request)
    {
        if(!$request->user()) {
            return redirect(route('home'));
        }

        $portfolio = Portfolio::whereUserId($request->user()->id)->first();

        if(!$portfolio) {
            $portfolio = new Portfolio;
            $portfolio->user_id = $request->user()->id;
            $portfolio->profile_id = $request->user()->profile_id;
            $portfolio->save();
        }

        return view('portfolio.settings', compact('portfolio'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user(), 404);

        $this->validate($request, [
            'profile_source' => 'required|in:recent,custom',
            'layout' => 'required|in:grid,masonry',
            'layout_container' => 'required|in:fixed,fluid'
        ]);

        $portfolio = Portfolio::whereUserId($request->user()->id)->first();

        if(!$portfolio) {
            $portfolio = new Portfolio;
            $portfolio->user_id = $request->user()->id;
            $portfolio->profile_id = $request->user()->profile_id;
            $portfolio->save();
        }

        $portfolio->active = $request->input('enabled') === 'on';
        $portfolio->show_captions = $request->input('show_captions') === 'on';
        $portfolio->show_license = $request->input('show_license') === 'on';
        $portfolio->show_location = $request->input('show_location') === 'on';
        $portfolio->show_timestamp = $request->input('show_timestamp') === 'on';
        $portfolio->show_link = $request->input('show_link') === 'on';
        $portfolio->profile_source = $request->input('profile_source');
        $portfolio->show_avatar = $request->input('show_avatar') === 'on';
        $portfolio->show_bio = $request->input('show_bio') === 'on';
        $portfolio->profile_layout = $request->input('layout');
        $portfolio->profile_container = $request->input('layout_container');
        $portfolio->save();

        return redirect('/' . $request->user()->username);
    }

    public function getFeed(Request $request, $id)
    {
        $user = AccountService::get($id, true);

        if(!$user || !isset($user['id'])) {
            return response()->json([], 404);
        }

        $portfolio = Portfolio::whereProfileId($user['id'])->first();

        if(!$portfolio || !$portfolio->active) {
            return response()->json([], 404);
        }

        if($portfolio->profile_source === 'custom' && $portfolio->metadata) {
            return $this->getCustomFeed($portfolio);
        }

        return $this->getRecentFeed($user['id']);
    }

    protected function getCustomFeed($portfolio) {
        if(!$portfolio->metadata['posts']) {
            return response()->json([], 400);
        }

        return collect($portfolio->metadata['posts'])->map(function($p) {
            return StatusService::get($p);
        })
        ->filter(function($p) {
            return $p && isset($p['account']);
        })->values();
    }

    protected function getRecentFeed($id) {
        $media = Cache::remember('portfolio:recent-feed:' . $id, 3600, function() use($id) {
            return DB::table('media')
            ->whereProfileId($id)
            ->whereNotNull('status_id')
            ->groupBy('status_id')
            ->orderByDesc('id')
            ->take(50)
            ->pluck('status_id');
        });

        return $media->map(function($sid) use($id) {
            return StatusService::get($sid);
        })
        ->filter(function($post) {
            return $post &&
                isset($post['media_attachments']) &&
                !empty($post['media_attachments']) &&
                $post['pf_type'] === 'photo' &&
                $post['visibility'] === 'public';
        })
        ->take(24)
        ->values();
    }

    public function getSettings(Request $request)
    {
        abort_if(!$request->user(), 403);

        $res = Portfolio::whereUserId($request->user()->id)->get();

        if(!$res) {
            return [];
        }

        return $res->map(function($p) {
            return [
                'url' => $p->url(),
                'pid' => (string) $p->profile_id,
                'active' => (bool) $p->active,
                'show_captions' => (bool) $p->show_captions,
                'show_license' => (bool) $p->show_license,
                'show_location' => (bool) $p->show_location,
                'show_timestamp' => (bool) $p->show_timestamp,
                'show_link' => (bool) $p->show_link,
                'show_avatar' => (bool) $p->show_avatar,
                'show_bio' => (bool) $p->show_bio,
                'profile_layout' => $p->profile_layout,
                'profile_source' => $p->profile_source,
                'metadata' => $p->metadata
            ];
        })->first();
    }

    public function getAccountSettings(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        $account = AccountService::get($request->input('id'));

        abort_if(!$account, 404);

        $p = Portfolio::whereProfileId($request->input('id'))->whereActive(1)->firstOrFail();

        if(!$p) {
            return [];
        }

        return [
            'url' => $p->url(),
            'show_captions' => (bool) $p->show_captions,
            'show_license' => (bool) $p->show_license,
            'show_location' => (bool) $p->show_location,
            'show_timestamp' => (bool) $p->show_timestamp,
            'show_link' => (bool) $p->show_link,
            'show_avatar' => (bool) $p->show_avatar,
            'show_bio' => (bool) $p->show_bio,
            'profile_layout' => $p->profile_layout,
            'profile_source' => $p->profile_source
        ];
    }

    public function storeSettings(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'profile_layout' => 'sometimes|in:grid,masonry,album'
        ]);

        $res = Portfolio::whereUserId($request->user()->id)
        ->update($request->only([
            'active',
            'show_captions',
            'show_license',
            'show_location',
            'show_timestamp',
            'show_link',
            'show_avatar',
            'show_bio',
            'profile_layout',
            'profile_source'
        ]));

        Cache::forget('portfolio:recent-feed:' . $request->user()->profile_id);

        return 200;
    }

    public function storeCurated(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'ids' => 'required|array|max:24'
        ]);

        $pid = $request->user()->profile_id;

        $ids = $request->input('ids');

        Status::whereProfileId($pid)
            ->whereScope('public')
            ->whereIn('type', ['photo', 'photo:album'])
            ->findOrFail($ids);

        $p = Portfolio::whereProfileId($pid)->firstOrFail();
        $p->metadata = ['posts' => $ids];
        $p->save();

        Cache::forget('portfolio:recent-feed:' . $pid);

        return $request->ids;
    }
}
