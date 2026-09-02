<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Jobs\HomeFeedPipeline\FeedWarmCachePipeline;
use App\Models\CustomFilter;
use App\Models\Hashtag;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Services\AccountService;
use App\Services\AdminShadowFilterService;
use App\Services\BookmarkService;
use App\Services\FollowerService;
use App\Services\HomeTimelineService;
use App\Services\LikeService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\ReblogService;
use App\Services\SnowflakeService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use App\Services\UserRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait Timelines
{
    /**
     * GET /api/v1/timelines/home
     *
     *
     * @return StatusTransformer
     */
    public function timelineHome(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'page' => 'sometimes|integer|max:40',
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'sometimes|integer|min:1',
            'include_reblogs' => 'sometimes',
        ]);

        $napi = $request->has(self::PF_API_ENTITY_KEY);
        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 20;
        if ($limit > 40) {
            $limit = 40;
        }
        $pid = $request->user()->profile_id;
        $userSettings = $request->user()->settings;
        $other = $userSettings->other ?? [];

        $userEnableReblogs = data_get($other, 'enable_reblogs', false);
        $includeReblogs = $request->filled('include_reblogs') ? $request->boolean('include_reblogs') : $userEnableReblogs;

        $nullFields = $includeReblogs ?
            ['in_reply_to_id'] :
            ['in_reply_to_id', 'reblog_of_id'];
        $inTypes = $includeReblogs ?
            ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album', 'share'] :
            ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'];
        AccountService::setLastActive($request->user()->id);

        $cachedFilters = CustomFilter::getCachedFiltersForAccount($pid);

        $homeFilters = array_filter($cachedFilters, function ($item) {
            [$filter, $rules] = $item;

            return in_array('home', $filter->context);
        });

        if (config('exp.cached_home_timeline')) {
            $paddedLimit = $includeReblogs ? $limit + 10 : $limit + 50;
            if ($min || $max) {
                if ($request->has('min_id')) {
                    $res = HomeTimelineService::getRankedMinId($pid, $min ?? 0, $paddedLimit);
                } else {
                    $res = HomeTimelineService::getRankedMaxId($pid, $max ?? 0, $paddedLimit);
                }
            } else {
                $res = HomeTimelineService::get($pid, 0, $paddedLimit);
            }

            if (! $res) {
                $res = Cache::has('pf:services:apiv1:home:cached:coldbootcheck:'.$pid);
                if (! $res) {
                    Cache::set('pf:services:apiv1:home:cached:coldbootcheck:'.$pid, 1, 86400);
                    FeedWarmCachePipeline::dispatchSync($pid);

                    return response()->json([], 206);
                } else {
                    Cache::set('pf:services:apiv1:home:cached:coldbootcheck:'.$pid, 1, 86400);

                    return response()->json([], 206);
                }
            }

            $res = collect($res)
                ->map(function ($id) use ($napi) {
                    return $napi ? StatusService::get($id, false) : StatusService::getMastodon($id, false);
                })
                ->filter(function ($res) {
                    return $res && isset($res['account']);
                })
                ->filter(function ($s) use ($includeReblogs) {
                    return $includeReblogs ? true : $s['reblog'] == null;
                })
                ->map(function ($status) use ($homeFilters) {
                    $filterResults = CustomFilter::applyCachedFilters($homeFilters, $status);

                    if (! empty($filterResults)) {
                        $status['filtered'] = $filterResults;
                        $shouldHide = collect($filterResults)->contains(function ($result) {
                            return $result['filter']['filter_action'] === 'hide';
                        });

                        if ($shouldHide) {
                            return null;
                        }
                    }

                    return $status;
                })
                ->filter()
                ->take($limit)
                ->map(function ($status) use ($pid) {
                    if ($pid) {
                        $status['favourited'] = (bool) LikeService::liked($pid, $status['id']);
                        $status['reblogged'] = (bool) ReblogService::get($pid, $status['id']);
                        $status['bookmarked'] = (bool) BookmarkService::get($pid, $status['id']);
                    }

                    return $status;
                })
                ->values();

            $baseUrl = config('app.url').'/api/v1/timelines/home?limit='.$limit.'&';
            $minId = $res->map(function ($s) {
                return ['id' => $s['id']];
            })->min('id');
            $maxId = $res->map(function ($s) {
                return ['id' => $s['id']];
            })->max('id');

            if ($minId == $maxId) {
                $minId = null;
            }

            if ($maxId && $res->count() >= $limit) {
                $link = '<'.$baseUrl.'max_id='.$minId.'>; rel="next"';
            }

            if ($minId) {
                $link = '<'.$baseUrl.'min_id='.$maxId.'>; rel="prev"';
            }

            if ($maxId && $minId) {
                $link = '<'.$baseUrl.'max_id='.$minId.'>; rel="next",<'.$baseUrl.'min_id='.$maxId.'>; rel="prev"';
            }

            $headers = isset($link) ? ['Link' => $link] : [];

            return $this->json($res->toArray(), 200, $headers);
        }

        $following = FollowerService::getFollowingIds($pid);

        $muted = UserFilterService::mutes($pid);

        if ($muted && count($muted)) {
            $following = array_diff($following, $muted);
        }

        if ($min || $max) {
            $dir = $min ? '>' : '<';
            $id = $min ?? $max;
            $res = Status::select(
                'id',
                'profile_id',
                'type',
                'visibility',
                'in_reply_to_id',
                'reblog_of_id'
            )
                ->where('id', $dir, $id)
                ->whereNull($nullFields)
                ->whereIntegerInRaw('profile_id', $following)
                ->whereIn('type', $inTypes)
                ->whereIn('visibility', ['public', 'unlisted', 'private'])
                ->orderByDesc('id')
                ->take(($limit * 2))
                ->get()
                ->map(function ($s) use ($pid, $napi) {
                    try {
                        $account = $napi ? AccountService::get($s['profile_id'], true) : AccountService::getMastodon($s['profile_id'], true);
                        if (! $account) {
                            return false;
                        }
                        $status = $napi ? StatusService::get($s['id'], false) : StatusService::getMastodon($s['id'], false);
                        if (! $status || ! isset($status['account']) || ! isset($status['account']['id'])) {
                            return false;
                        }
                    } catch (\Exception $e) {
                        return false;
                    }

                    $status['account'] = $account;

                    if ($pid) {
                        $status['favourited'] = (bool) LikeService::liked($pid, $s['id']);
                        $status['reblogged'] = (bool) ReblogService::get($pid, $status['id']);
                        $status['bookmarked'] = (bool) BookmarkService::get($pid, $status['id']);
                    }

                    return $status;
                })
                ->filter(function ($status) {
                    return $status && isset($status['account']);
                })
                ->map(function ($status) use ($pid) {
                    if (! empty($status['reblog'])) {
                        $status['reblog']['favourited'] = (bool) LikeService::liked($pid, $status['reblog']['id']);
                        $status['reblog']['reblogged'] = (bool) ReblogService::get($pid, $status['reblog']['id']);
                        $status['bookmarked'] = (bool) BookmarkService::get($pid, $status['id']);
                    }

                    return $status;
                })
                ->map(function ($status) use ($homeFilters) {
                    $filterResults = CustomFilter::applyCachedFilters($homeFilters, $status);

                    if (! empty($filterResults)) {
                        $status['filtered'] = $filterResults;
                        $shouldHide = collect($filterResults)->contains(function ($result) {
                            return $result['filter']['filter_action'] === 'hide';
                        });

                        if ($shouldHide) {
                            return null;
                        }
                    }

                    return $status;
                })
                ->filter()
                ->take($limit)
                ->values();
        } else {
            $res = Status::select(
                'id',
                'profile_id',
                'type',
                'visibility',
                'in_reply_to_id',
                'reblog_of_id',
            )
                ->whereNull($nullFields)
                ->whereIntegerInRaw('profile_id', $following)
                ->whereIn('type', $inTypes)
                ->whereIn('visibility', ['public', 'unlisted', 'private'])
                ->orderByDesc('id')
                ->take(($limit * 2))
                ->get()
                ->map(function ($s) use ($pid, $napi) {
                    try {
                        $account = $napi ? AccountService::get($s['profile_id'], true) : AccountService::getMastodon($s['profile_id'], true);
                        if (! $account) {
                            return false;
                        }
                        $status = $napi ? StatusService::get($s['id'], false) : StatusService::getMastodon($s['id'], false);
                        if (! $status || ! isset($status['account']) || ! isset($status['account']['id'])) {
                            return false;
                        }
                    } catch (\Exception $e) {
                        return false;
                    }

                    $status['account'] = $account;

                    if ($pid) {
                        $status['favourited'] = (bool) LikeService::liked($pid, $s['id']);
                        $status['reblogged'] = (bool) ReblogService::get($pid, $status['id']);
                        $status['bookmarked'] = (bool) BookmarkService::get($pid, $status['id']);
                    }

                    return $status;
                })
                ->filter(function ($status) {
                    return $status && isset($status['account']);
                })
                ->map(function ($status) use ($pid) {
                    if (! empty($status['reblog'])) {
                        $status['reblog']['favourited'] = (bool) LikeService::liked($pid, $status['reblog']['id']);
                        $status['reblog']['reblogged'] = (bool) ReblogService::get($pid, $status['reblog']['id']);
                        $status['bookmarked'] = (bool) BookmarkService::get($pid, $status['id']);
                    }

                    return $status;
                })
                ->map(function ($status) use ($homeFilters) {
                    $filterResults = CustomFilter::applyCachedFilters($homeFilters, $status);

                    if (! empty($filterResults)) {
                        $status['filtered'] = $filterResults;
                        $shouldHide = collect($filterResults)->contains(function ($result) {
                            return $result['filter']['filter_action'] === 'hide';
                        });

                        if ($shouldHide) {
                            return null;
                        }
                    }

                    return $status;
                })
                ->filter()
                ->take($limit)
                ->values();
        }

        $baseUrl = $napi ? config('app.url').'/api/v1/timelines/home?limit='.$limit.'&_pe=1&' : config('app.url').'/api/v1/timelines/home?limit='.$limit.'&';
        $minId = $res->map(function ($s) {
            return ['id' => $s['id']];
        })->min('id');
        $maxId = $res->map(function ($s) {
            return ['id' => $s['id']];
        })->max('id');

        if ($minId == $maxId) {
            $minId = null;
        }

        if ($maxId) {
            $link = '<'.$baseUrl.'max_id='.$minId.'>; rel="next"';
        }

        if ($minId) {
            $link = '<'.$baseUrl.'min_id='.$maxId.'>; rel="prev"';
        }

        if ($maxId && $minId) {
            $link = '<'.$baseUrl.'max_id='.$minId.'>; rel="next",<'.$baseUrl.'min_id='.$maxId.'>; rel="prev"';
        }

        $headers = isset($link) ? ['Link' => $link] : [];

        return $this->json($res->toArray(), 200, $headers);
    }

    /**
     * GET /api/v1/timelines/public
     *
     *
     * @return StatusTransformer
     */
    public function timelinePublic(Request $request)
    {
        $this->validate($request, [
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'sometimes|integer|min:1',
            'remote' => 'sometimes',
            'local' => 'sometimes',
        ]);

        $napi = $request->has(self::PF_API_ENTITY_KEY);
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        if ($max == 0) {
            $min = 1;
        }
        $minOrMax = $request->anyFilled(['max_id', 'min_id']);
        $limit = $request->input('limit') ?? 20;
        if ($limit > 40) {
            $limit = 40;
        }
        $user = $request->user();
        $pid = $user->profile_id;
        $remote = $request->has('remote') && $request->boolean('remote');
        $local = $request->boolean('local');
        $userRoleKey = $remote ? 'can-view-network-feed' : 'can-view-public-feed';
        if ($user->has_roles && ! UserRoleService::can($userRoleKey, $user->id)) {
            return [];
        }
        $filtered = $user ? UserFilterService::filters($user->profile_id) : [];
        AccountService::setLastActive($user->id);
        $domainBlocks = UserFilterService::domainBlocks($user->profile_id);
        $hideNsfw = config('instance.hide_nsfw_on_public_feeds');
        $amin = SnowflakeService::byDate(now()->subDays(config('federation.network_timeline_days_falloff')));
        $asf = AdminShadowFilterService::getHideFromPublicFeedsList();

        $cachedFilters = CustomFilter::getCachedFiltersForAccount($pid);

        $homeFilters = array_filter($cachedFilters, function ($item) {
            [$filter, $rules] = $item;

            return in_array('public', $filter->context);
        });
        if ($local && $remote) {
            $feed = Status::select(
                'id',
                'uri',
                'type',
                'scope',
                'created_at',
                'profile_id',
                'in_reply_to_id',
                'reblog_of_id'
            )
                ->when($minOrMax, function ($q, $minOrMax) use ($min, $max) {
                    $dir = $min ? '>' : '<';
                    $id = $min ?? $max;

                    return $q->where('id', $dir, $id);
                })
                ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                ->when($hideNsfw, function ($q, $hideNsfw) {
                    return $q->where('is_nsfw', false);
                })
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereScope('public')
                ->where('id', '>', $amin)
                ->orderByDesc('id')
                ->limit(($limit * 2))
                ->pluck('id')
                ->values()
                ->toArray();
        } elseif ($remote && ! $local) {
            if (config('instance.timeline.network.cached')) {
                Cache::remember('api:v1:timelines:network:cache_check', 10368000, function () {
                    if (NetworkTimelineService::count() == 0) {
                        NetworkTimelineService::warmCache(true, config('instance.timeline.network.cache_dropoff'));
                    }
                });

                if ($max) {
                    $feed = NetworkTimelineService::getRankedMaxId($max, $limit + 5);
                } elseif ($min) {
                    $feed = NetworkTimelineService::getRankedMinId($min, $limit + 5);
                } else {
                    $feed = NetworkTimelineService::get(0, $limit + 5);
                }
            } else {
                $feed = Status::select(
                    'id',
                    'uri',
                    'type',
                    'scope',
                    'local',
                    'created_at',
                    'profile_id',
                    'in_reply_to_id',
                    'reblog_of_id'
                )
                    ->when($minOrMax, function ($q, $minOrMax) use ($min, $max) {
                        $dir = $min ? '>' : '<';
                        $id = $min ?? $max;

                        return $q->where('id', $dir, $id);
                    })
                    ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                    ->when($hideNsfw, function ($q, $hideNsfw) {
                        return $q->where('is_nsfw', false);
                    })
                    ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                    ->whereLocal(false)
                    ->whereScope('public')
                    ->where('id', '>', $amin)
                    ->orderByDesc('id')
                    ->limit(($limit * 2))
                    ->pluck('id')
                    ->values()
                    ->toArray();
            }
        } else {
            if (config('instance.timeline.local.cached')) {
                Cache::remember('api:v1:timelines:public:cache_check', 10368000, function () {
                    if (PublicTimelineService::count() == 0) {
                        PublicTimelineService::warmCache(true, 400);
                    }
                });

                if ($max) {
                    $feed = PublicTimelineService::getRankedMaxId($max, $limit + 5);
                } elseif ($min) {
                    $feed = PublicTimelineService::getRankedMinId($min, $limit + 5);
                } else {
                    $feed = PublicTimelineService::get(0, $limit + 5);
                }
            } else {
                $feed = Status::select(
                    'id',
                    'uri',
                    'type',
                    'scope',
                    'local',
                    'created_at',
                    'profile_id',
                    'in_reply_to_id',
                    'reblog_of_id'
                )
                    ->when($minOrMax, function ($q, $minOrMax) use ($min, $max) {
                        $dir = $min ? '>' : '<';
                        $id = $min ?? $max;

                        return $q->where('id', $dir, $id);
                    })
                    ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                    ->when($hideNsfw, function ($q, $hideNsfw) {
                        return $q->where('is_nsfw', false);
                    })
                    ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                    ->whereLocal(true)
                    ->whereScope('public')
                    ->where('id', '>', $amin)
                    ->orderByDesc('id')
                    ->limit(($limit * 2))
                    ->pluck('id')
                    ->values()
                    ->toArray();
            }
        }

        $res = collect($feed)
            ->filter(function ($k) use ($min, $max) {
                if (! $min && ! $max) {
                    return true;
                }

                if ($min) {
                    return $min != $k;
                }

                if ($max) {
                    return $max != $k;
                }
            })
            ->map(function ($k) use ($user, $napi) {
                try {
                    $status = $napi ? StatusService::get($k) : StatusService::getMastodon($k);
                    if (! $status || ! isset($status['account']) || ! isset($status['account']['id'])) {
                        return false;
                    }
                } catch (\Exception $e) {
                    return false;
                }

                $account = $napi ? AccountService::get($status['account']['id'], true) : AccountService::getMastodon($status['account']['id'], true);
                if (! $account) {
                    return false;
                }

                $status['account'] = $account;

                if ($user) {
                    $status['favourited'] = (bool) LikeService::liked($user->profile_id, $k);
                    $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $status['id']);
                    $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $status['id']);
                }

                return $status;
            })
            ->filter(function ($s) use ($filtered) {
                return $s && isset($s['account']) && in_array($s['account']['id'], $filtered) == false;
            })
            ->filter(function ($s) use ($domainBlocks) {
                if (! $domainBlocks || ! count($domainBlocks)) {
                    return $s;
                }
                $domain = strtolower(parse_url($s['url'], PHP_URL_HOST));

                return ! in_array($domain, $domainBlocks);
            })
            ->filter(function ($s) use ($asf, $user) {
                if (! $asf || count($asf) === 0) {
                    return true;
                }

                if (in_array($s['account']['id'], $asf)) {
                    if ($user->profile_id == $s['account']['id']) {
                        return true;
                    }

                    return false;
                }

                return true;
            })
            ->map(function ($status) use ($homeFilters) {
                $filterResults = CustomFilter::applyCachedFilters($homeFilters, $status);

                if (! empty($filterResults)) {
                    $status['filtered'] = $filterResults;
                    $shouldHide = collect($filterResults)->contains(function ($result) {
                        return $result['filter']['filter_action'] === 'hide';
                    });

                    if ($shouldHide) {
                        return null;
                    }
                }

                return $status;
            })
            ->filter()
            ->take($limit)
            ->values();

        $baseUrl = $napi ? config('app.url').'/api/v1/timelines/public?limit='.$limit.'&_pe=1&' : config('app.url').'/api/v1/timelines/public?limit='.$limit.'&';
        if ($remote) {
            $baseUrl .= 'remote=1&';
        }
        if ($local) {
            $baseUrl .= 'local=1&';
        }
        $minId = $res->map(function ($s) {
            return ['id' => $s['id']];
        })->min('id');
        $maxId = $res->map(function ($s) {
            return ['id' => $s['id']];
        })->max('id');

        if ($minId == $maxId) {
            $minId = null;
        }

        if ($maxId && $res->count() >= $limit) {
            $link = '<'.$baseUrl.'max_id='.$minId.'>; rel="next"';
        }

        if ($minId) {
            $link = '<'.$baseUrl.'min_id='.$maxId.'>; rel="prev"';
        }

        if ($maxId && $minId) {
            $link = '<'.$baseUrl.'max_id='.$minId.'>; rel="next",<'.$baseUrl.'min_id='.$maxId.'>; rel="prev"';
        }

        $headers = isset($link) ? ['Link' => $link] : [];

        return $this->json($res->toArray(), 200, $headers);
    }

    /**
     * GET /api/v1/timelines/tag/{hashtag}
     *
     * @param  string  $hashtag
     * @return StatusTransformer
     */
    public function timelineHashtag(Request $request, $hashtag)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'page' => 'nullable|integer|max:40',
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'sometimes|integer|min:1',
            'only_media' => 'sometimes',
            '_pe' => 'sometimes',
        ]);

        $user = $request->user();
        abort_if(
            $user->has_roles && ! UserRoleService::can('can-view-hashtag-feed', $user->id),
            403,
            'Invalid permissions for this action'
        );

        if (config('database.default') === 'pgsql') {
            $tag = Hashtag::where('name', 'ilike', $hashtag)
                ->orWhere('slug', 'ilike', $hashtag)
                ->first();
        } else {
            $tag = Hashtag::whereName($hashtag)
                ->orWhere('slug', $hashtag)
                ->first();
        }

        if (! $tag) {
            return response()->json([]);
        }

        if ($tag->is_banned == true) {
            return $this->json([]);
        }

        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit', 20);
        if ($limit > 40) {
            $limit = 40;
        }
        $onlyMedia = $request->boolean('only_media', true);
        $pe = $request->has(self::PF_API_ENTITY_KEY);
        $pid = $request->user()->profile_id;

        $cachedFilters = CustomFilter::getCachedFiltersForAccount($pid);

        $tagFilters = array_filter($cachedFilters, function ($item) {
            [$filter, $rules] = $item;

            return in_array('tags', $filter->context);
        });

        if ($min || $max) {
            $minMax = SnowflakeService::byDate(now()->subMonths(9));
            if ($min && intval($min) < $minMax) {
                return [];
            }
            if ($max && intval($max) < $minMax) {
                return [];
            }
        }

        $filters = UserFilterService::filters($pid);
        $domainBlocks = UserFilterService::domainBlocks($pid);

        if (! $min && ! $max) {
            $id = 1;
            $dir = '>';
        } else {
            $dir = $min ? '>' : '<';
            $id = $min ?? $max;
        }

        $res = StatusHashtag::whereHashtagId($tag->id)
            ->where('status_id', $dir, $id)
            ->whereIn('status_visibility', ['public', 'unlisted'])
            ->orderBy('status_id', 'desc')
            ->limit(100)
            ->pluck('status_id')
            ->map(function ($i) use ($pe) {
                return $pe ? StatusService::get($i, false) : StatusService::getMastodon($i, false);
            })
            ->filter(function ($i) use ($onlyMedia, $pid) {
                if (! $i || ! isset($i['account'], $i['account']['id'])) {
                    return false;
                }
                if ($i['visibility'] === 'unlisted') {
                    if ((int) $i['account']['id'] !== $pid) {
                        return false;
                    }
                }
                if ($i['visibility'] === 'private') {
                    if ((int) $i['account']['id'] !== $pid) {
                        return FollowerService::follows($pid, $i['account']['id'], true);
                    }
                }
                if ($onlyMedia == true) {
                    if (! isset($i['media_attachments']) || ! count($i['media_attachments'])) {
                        return false;
                    }
                }

                return $i && isset($i['account'], $i['url']);
            })
            ->filter(function ($i) use ($filters, $domainBlocks) {
                $domain = strtolower(parse_url($i['url'], PHP_URL_HOST));

                return ! in_array($i['account']['id'], $filters) && ! in_array($domain, $domainBlocks);
            })
            ->map(function ($status) use ($tagFilters) {
                $filterResults = CustomFilter::applyCachedFilters($tagFilters, $status);

                if (! empty($filterResults)) {
                    $status['filtered'] = $filterResults;
                    $shouldHide = collect($filterResults)->contains(function ($result) {
                        return $result['filter']['filter_action'] === 'hide';
                    });

                    if ($shouldHide) {
                        return null;
                    }
                }

                return $status;
            })
            ->filter()
            ->take($limit)
            ->values()
            ->toArray();

        return $this->json($res);
    }
}
