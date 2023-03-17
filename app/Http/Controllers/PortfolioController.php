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
	const RSS_FEED_KEY = 'pf:portfolio:rss-feed:';
	const CACHED_FEED_KEY = 'pf:portfolio:cached-feed:';
	const RECENT_FEED_KEY = 'pf:portfolio:recent-feed:';

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

        if(!$portfolio || $user['locked'] || $portfolio->active != true) {
            return view('portfolio.404');
        }

        if(!$post || $post['visibility'] != 'public' || !in_array($post['pf_type'], ['photo', 'photo:album']) || $user['id'] != $post['account']['id']) {
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
            'layout_container' => 'required|in:fixed,fluid',
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
        $portfolio->metadata = $metadata;
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
        if(!isset($portfolio->metadata['posts']) || !$portfolio->metadata['posts']) {
            return response()->json([], 400);
        }

        $feed = Cache::remember(self::CACHED_FEED_KEY . $portfolio->profile_id, 86400, function() use($portfolio) {
	        return collect($portfolio->metadata['posts'])->map(function($p) {
	            return StatusService::get($p);
	        })
	        ->filter(function($p) {
	            return $p && isset($p['account']);
	        });
        });

        if($portfolio->metadata && isset($portfolio->metadata['feed_order']) && $portfolio->metadata['feed_order'] === 'recent') {
        	return $feed->reverse()->values();
        } else {
        	return $feed->values();
        }
    }

    protected function getRecentFeed($id) {
        $media = Cache::remember(self::RECENT_FEED_KEY . $id, 3600, function() use($id) {
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
        	$metadata = $p->metadata;
        	$bgColor = $metadata && isset($metadata['background_color']) ? $metadata['background_color'] : '#000000';
        	$textColor = $metadata && isset($metadata['text_color']) ? $metadata['text_color'] : '#d4d4d8';
        	$rssEnabled = $metadata && isset($metadata['rss_enabled']) ? $metadata['rss_enabled'] : false;
        	$rssButton = $metadata && isset($metadata['show_rss_button']) ? $metadata['show_rss_button'] : false;
        	$colorScheme = $metadata && isset($metadata['color_scheme']) ? $metadata['color_scheme'] : 'dark';
        	$feedOrder = $metadata && isset($metadata['feed_order']) ? $metadata['feed_order'] : 'oldest';

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
                'color_scheme' => $colorScheme,
                'background_color' => $bgColor,
                'text_color' => $textColor,
                'show_profile_button' => true,
                'rss_enabled' => $rssEnabled,
                'show_rss_button' => $rssButton,
                'feed_order' => $feedOrder,
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
        $metadata = $p->metadata;

        $rssEnabled = $metadata && isset($metadata['rss_enabled']) ? $metadata['rss_enabled'] : false;
        $rssButton = $metadata && isset($metadata['show_rss_button']) ? $metadata['show_rss_button'] : false;
        $profileButton = $metadata && isset($metadata['show_profile_button']) ? $metadata['show_profile_button'] : false;

        $res = [
            'url' => $p->url(),
            'show_captions' => (bool) $p->show_captions,
            'show_license' => (bool) $p->show_license,
            'show_location' => (bool) $p->show_location,
            'show_timestamp' => (bool) $p->show_timestamp,
            'show_link' => (bool) $p->show_link,
            'show_avatar' => (bool) $p->show_avatar,
            'show_bio' => (bool) $p->show_bio,
            'profile_layout' => $p->profile_layout,
            'profile_source' => $p->profile_source,
            'show_profile_button' => $profileButton,
            'rss_enabled' => $rssEnabled,
            'show_rss_button' => $rssButton,
        ];

        if($rssEnabled) {
        	$res['rss_feed_url'] = $p->permalink('.rss');
        }

        if($p->metadata) {
        	if(isset($p->metadata['background_color'])) {
        		$res['background_color'] = $p->metadata['background_color'];
        	}

        	if(isset($p->metadata['text_color'])) {
        		$res['text_color'] = $p->metadata['text_color'];
        	}
        }

        return $res;
    }

    public function storeSettings(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
        	'active' => 'sometimes|boolean',
        	'show_captions' => 'sometimes|boolean',
        	'show_license' => 'sometimes|boolean',
        	'show_location' => 'sometimes|boolean',
        	'show_timestamp' => 'sometimes|boolean',
        	'show_link' => 'sometimes|boolean',
        	'show_avatar' => 'sometimes|boolean',
        	'show_bio' => 'sometimes|boolean',
            'profile_layout' => 'sometimes|in:grid,masonry,album',
            'profile_source' => 'sometimes|in:recent,custom',
            'color_scheme' => 'sometimes|in:light,dark,custom',
            'show_profile_button' => 'sometimes|boolean',
            'rss_enabled' => 'sometimes|boolean',
            'show_rss_button' => 'sometimes|boolean',
            'feed_order' => 'sometimes|in:oldest,recent',
            'background_color' => [
				'sometimes',
				'nullable',
				'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'
			],
			'text_color' => [
				'sometimes',
				'nullable',
				'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'
			],
        ]);

        $res = Portfolio::whereUserId($request->user()->id)->firstOrFail();
        $pid = $request->user()->profile_id;
        $metadata = $res->metadata;
        $clearFeedCache = false;

        if($request->has('color_scheme')) {
        	$metadata['color_scheme'] = $request->input('color_scheme');
        }

        if($request->has('background_color')) {
        	$metadata['background_color'] = $request->input('background_color');
        	$bgc = $request->background_color;
        	if($bgc && $bgc !== '#000000') {
        		$metadata['color_scheme'] = 'custom';
        	}
        }

        if($request->has('text_color')) {
        	$metadata['text_color'] = $request->input('text_color');
        	$txc = $request->text_color;
        	if($txc && $txc !== '#d4d4d8') {
        		$metadata['color_scheme'] = 'custom';
        	}
        }

        if($request->has('show_profile_button')) {
        	$metadata['show_profile_button'] = $request->input('show_profile_button');
        }

        if($request->has('rss_enabled')) {
        	$metadata['rss_enabled'] = $request->input('rss_enabled');
        }

        if($request->has('show_rss_button')) {
        	$metadata['show_rss_button'] = $metadata['rss_enabled'] ? $request->input('show_rss_button') : false;
        }

        if($request->has('feed_order')) {
        	$metadata['feed_order'] = $request->input('feed_order');
        }

        if(isset($metadata['background_color']) || isset($metadata['text_color'])) {
        	$bgc = isset($metadata['background_color']) ? $metadata['background_color'] : null;
        	$txc = isset($metadata['text_color']) ? $metadata['text_color'] : null;

        	if((!$bgc || $bgc == '#000000') && (!$txc || $txc === '#d4d4d8') && $request->color_scheme != 'light') {
        		$metadata['color_scheme'] = 'dark';
        	}
        }

        if($request->has('color_scheme') && $request->color_scheme === 'light') {
        	$metadata['background_color'] = '#ffffff';
        	$metadata['text_color'] = '#000000';
        	$metadata['color_scheme'] = 'light';
        }

        if($request->metadata !== $metadata) {
        	$res->metadata = $metadata;
        	$res->save();
        }

        if($request->profile_layout != $res->profile_layout) {
        	$clearFeedCache = true;
        }

        $res->update($request->only([
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

        Cache::forget(self::RECENT_FEED_KEY . $pid);

        if($clearFeedCache) {
        	Cache::forget(self::RSS_FEED_KEY . $pid);
        }

        return 200;
    }

    public function storeCurated(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'ids' => 'required|array|max:100'
        ]);

        $pid = $request->user()->profile_id;

        $ids = $request->input('ids');

        Status::whereProfileId($pid)
            ->whereScope('public')
            ->whereIn('type', ['photo', 'photo:album'])
            ->findOrFail($ids);

        $p = Portfolio::whereProfileId($pid)->firstOrFail();
        $metadata = $p->metadata;
        $metadata['posts'] = $ids;
        $p->metadata = $metadata;
        $p->save();

        Cache::forget(self::RECENT_FEED_KEY . $pid);
        Cache::forget(self::RSS_FEED_KEY . $pid);
        Cache::forget(self::CACHED_FEED_KEY . $pid);

        return $request->ids;
    }

    public function getRssFeed(Request $request, $username)
    {
    	$user = User::whereUsername($username)->first();

        if(!$user) {
            return view('portfolio.404');
        }

        $portfolio = Portfolio::whereUserId($user->id)->where('active', 1)->firstOrFail();

        $metadata = $portfolio->metadata;

        abort_if(!$metadata || !isset($metadata['rss_enabled']), 404);
        abort_unless($metadata['rss_enabled'], 404);

        $account = AccountService::get($user->profile_id);
        $portfolioUrl = $portfolio->url();
        $portfolioLayout = $portfolio->profile_layout;

        if(!isset($metadata['posts']) || !count($metadata['posts'])) {
        	$feed = [];
        } else {
        	$feed = Cache::remember(
        		self::RSS_FEED_KEY . $user->profile_id,
        		43200,
        		function() use($portfolio, $portfolioUrl, $portfolioLayout) {
					return collect($portfolio->metadata['posts'])->map(function($post) {
						return StatusService::get($post);
					})
					->filter()
					->values()
					->map(function($post, $idx) use($portfolioLayout, $portfolioUrl) {
						$ts = now()->parse($post['created_at']);
						$url = $portfolioLayout == 'album' ? $portfolioUrl . '?slide=' . ($idx + 1) : $portfolioUrl . '/' . $post['id'];
						return [
							'title' => 'Post by ' . $post['account']['username'] . ' on ' . $ts->format('D, d M Y'),
							'description' => $post['content_text'],
							'pubDate' => date('D, d M Y H:i:s ', strtotime($post['created_at'])) . 'GMT',
							'url' => $url
						];
					})
					->reverse()
					->take(10)
					->toArray();
        		}
        	);
        }

		$now = date('D, d M Y H:i:s ') . 'GMT';

		return response()
			->view('portfolio.rss_feed', compact('account', 'now', 'feed', 'portfolioUrl'), 200)
			->header('Content-Type', 'text/xml');
        return response($feed)->withHeaders(['Content-Type' => 'text/xml']);
    }


    public function getApFeed(Request $request, $username)
    {
    	$user = User::whereUsername($username)->first();

        if(!$user) {
            return view('portfolio.404');
        }

        $portfolio = Portfolio::whereUserId($user->id)->where('active', 1)->firstOrFail();
        $metadata = $portfolio->metadata;
        $baseUrl = config('app.url');
        $page = $request->input('page');

        $res = [
        	'@context' => 'https://www.w3.org/ns/activitystreams',
        	'id' => $portfolio->permalink('.json'),
        	'type' => 'OrderedCollection',
        	'totalItems' => isset($metadata['posts']) ? count($metadata['posts']) : 0,
        ];

        if($request->has('page')) {
        	$start = $page == 1 ? 0 : ($page * 10 - 10);
        	$res['id'] = $portfolio->permalink('.json?page=' . $page);
        	$res['type'] = 'OrderedCollectionPage';
        	$res['next'] = $portfolio->permalink('.json?page=' . $page + 1);
        	$res['partOf'] = $portfolio->permalink('.json');
        	$res['orderedItems'] = collect($metadata['posts'])->slice($start)->take(10)->map(function($p) {
        		return StatusService::get($p);
        	})
        	->filter()
        	->map(function($p) {
        		return $p['url'];
        	})
        	->values();

        	if(!$res['orderedItems'] || $res['orderedItems']->count() != 10) {
        		unset($res['next']);
        	}
        } else {
        	$res['first'] = $portfolio->permalink('.json?page=1');
        }
        return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)
        ->header('Content-Type', 'application/activity+json');
    }
}
