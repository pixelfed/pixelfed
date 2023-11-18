<?php

namespace App\Http\Controllers\Stories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Conversation;
use App\DirectMessage;
use App\Notification;
use App\Story;
use App\Status;
use App\StoryView;
use App\Jobs\StoryPipeline\StoryDelete;
use App\Jobs\StoryPipeline\StoryFanout;
use App\Jobs\StoryPipeline\StoryReplyDeliver;
use App\Jobs\StoryPipeline\StoryViewDeliver;
use App\Services\AccountService;
use App\Services\MediaPathService;
use App\Services\StoryService;
use App\Http\Resources\StoryView as StoryViewResource;

class StoryApiV1Controller extends Controller
{
    const RECENT_KEY = 'pf:stories:recent-by-id:';
    const RECENT_TTL = 300;

    public function carousel(Request $request)
    {
        abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);
        $pid = $request->user()->profile_id;

        if(config('database.default') == 'pgsql') {
            $s = Cache::remember(self::RECENT_KEY . $pid, self::RECENT_TTL, function() use($pid) {
                return Story::select('stories.*', 'followers.following_id')
                    ->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
                    ->where('followers.profile_id', $pid)
                    ->where('stories.active', true)
                    ->map(function($s) {
                        $r  = new \StdClass;
                        $r->id = $s->id;
                        $r->profile_id = $s->profile_id;
                        $r->type = $s->type;
                        $r->path = $s->path;
                        return $r;
                    })
                    ->unique('profile_id');
            });
        } else {
            $s = Cache::remember(self::RECENT_KEY . $pid, self::RECENT_TTL, function() use($pid) {
                return Story::select('stories.*', 'followers.following_id')
                    ->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
                    ->where('followers.profile_id', $pid)
                    ->where('stories.active', true)
                    ->orderBy('id')
                    ->get();
            });
        }

        $nodes = $s->map(function($s) use($pid) {
            $profile = AccountService::get($s->profile_id, true);
            if(!$profile || !isset($profile['id'])) {
                return false;
            }

            return [
                'id' => (string) $s->id,
                'pid' => (string) $s->profile_id,
                'type' => $s->type,
                'src' => url(Storage::url($s->path)),
                'duration' => $s->duration ?? 3,
                'seen' => StoryService::hasSeen($pid, $s->id),
                'created_at' => $s->created_at->format('c')
            ];
        })
        ->filter()
        ->groupBy('pid')
        ->map(function($item) use($pid) {
            $profile = AccountService::get($item[0]['pid'], true);
            $url = $profile['local'] ? url("/stories/{$profile['username']}") :
                url("/i/rs/{$profile['id']}");
            return [
                'id' => 'pfs:' . $profile['id'],
                'user' => [
                    'id' => (string) $profile['id'],
                    'username' => $profile['username'],
                    'username_acct' => $profile['acct'],
                    'avatar' => $profile['avatar'],
                    'local' => $profile['local'],
                    'is_author' => $profile['id'] == $pid
                ],
                'nodes' => $item,
                'url' => $url,
                'seen' => StoryService::hasSeen($pid, StoryService::latest($profile['id'])),
            ];
        })
        ->sortBy('seen')
        ->values();

        $res = [
            'self' => [],
            'nodes' => $nodes,
        ];

        if(Story::whereProfileId($pid)->whereActive(true)->exists()) {
            $selfStories = Story::whereProfileId($pid)
                ->whereActive(true)
                ->get()
                ->map(function($s) use($pid) {
                    return [
                        'id' => (string) $s->id,
                        'type' => $s->type,
                        'src' => url(Storage::url($s->path)),
                        'duration' => $s->duration,
                        'seen' => true,
                        'created_at' => $s->created_at->format('c')
                    ];
                })
                ->sortBy('id')
                ->values();
            $selfProfile = AccountService::get($pid, true);
            $res['self'] = [
                'user' => [
                    'id' => (string) $selfProfile['id'],
                    'username' => $selfProfile['acct'],
                    'avatar' => $selfProfile['avatar'],
                    'local' => $selfProfile['local'],
                    'is_author' => true
                ],

                'nodes' => $selfStories,
            ];
        }
        return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function selfCarousel(Request $request)
    {
        abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);
        $pid = $request->user()->profile_id;

        if(config('database.default') == 'pgsql') {
            $s = Cache::remember(self::RECENT_KEY . $pid, self::RECENT_TTL, function() use($pid) {
                return Story::select('stories.*', 'followers.following_id')
                    ->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
                    ->where('followers.profile_id', $pid)
                    ->where('stories.active', true)
                    ->map(function($s) {
                        $r  = new \StdClass;
                        $r->id = $s->id;
                        $r->profile_id = $s->profile_id;
                        $r->type = $s->type;
                        $r->path = $s->path;
                        return $r;
                    })
                    ->unique('profile_id');
            });
        } else {
            $s = Cache::remember(self::RECENT_KEY . $pid, self::RECENT_TTL, function() use($pid) {
                return Story::select('stories.*', 'followers.following_id')
                    ->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
                    ->where('followers.profile_id', $pid)
                    ->where('stories.active', true)
                    ->orderBy('id')
                    ->get();
            });
        }

        $nodes = $s->map(function($s) use($pid) {
            $profile = AccountService::get($s->profile_id, true);
            if(!$profile || !isset($profile['id'])) {
                return false;
            }

            return [
                'id' => (string) $s->id,
                'pid' => (string) $s->profile_id,
                'type' => $s->type,
                'src' => url(Storage::url($s->path)),
                'duration' => $s->duration ?? 3,
                'seen' => StoryService::hasSeen($pid, $s->id),
                'created_at' => $s->created_at->format('c')
            ];
        })
        ->filter()
        ->groupBy('pid')
        ->map(function($item) use($pid) {
            $profile = AccountService::get($item[0]['pid'], true);
            $url = $profile['local'] ? url("/stories/{$profile['username']}") :
                url("/i/rs/{$profile['id']}");
            return [
                'id' => 'pfs:' . $profile['id'],
                'user' => [
                    'id' => (string) $profile['id'],
                    'username' => $profile['username'],
                    'username_acct' => $profile['acct'],
                    'avatar' => $profile['avatar'],
                    'local' => $profile['local'],
                    'is_author' => $profile['id'] == $pid
                ],
                'nodes' => $item,
                'url' => $url,
                'seen' => StoryService::hasSeen($pid, StoryService::latest($profile['id'])),
            ];
        })
        ->sortBy('seen')
        ->values();

        $selfProfile = AccountService::get($pid, true);
        $res = [
            'self' => [
                'user' => [
                    'id' => (string) $selfProfile['id'],
                    'username' => $selfProfile['acct'],
                    'avatar' => $selfProfile['avatar'],
                    'local' => $selfProfile['local'],
                    'is_author' => true
                ],

                'nodes' => [],
            ],
            'nodes' => $nodes,
        ];

        if(Story::whereProfileId($pid)->whereActive(true)->exists()) {
            $selfStories = Story::whereProfileId($pid)
                ->whereActive(true)
                ->get()
                ->map(function($s) use($pid) {
                    return [
                        'id' => (string) $s->id,
                        'type' => $s->type,
                        'src' => url(Storage::url($s->path)),
                        'duration' => $s->duration,
                        'seen' => true,
                        'created_at' => $s->created_at->format('c')
                    ];
                })
                ->sortBy('id')
                ->values();
            $res['self']['nodes'] = $selfStories;
        }
        return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function add(Request $request)
    {
        abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

        $this->validate($request, [
            'file' => function() {
                return [
                    'required',
                    'mimetypes:image/jpeg,image/png,video/mp4',
                    'max:' . config_cache('pixelfed.max_photo_size'),
                ];
            },
            'duration' => 'sometimes|integer|min:0|max:30'
        ]);

        $user = $request->user();

        $count = Story::whereProfileId($user->profile_id)
            ->whereActive(true)
            ->where('expires_at', '>', now())
            ->count();

        if($count >= Story::MAX_PER_DAY) {
            abort(418, 'You have reached your limit for new Stories today.');
        }

        $photo = $request->file('file');
        $path = $this->storeMedia($photo, $user);

        $story = new Story();
        $story->duration = $request->input('duration', 3);
        $story->profile_id = $user->profile_id;
        $story->type = Str::endsWith($photo->getMimeType(), 'mp4') ? 'video' :'photo';
        $story->mime = $photo->getMimeType();
        $story->path = $path;
        $story->local = true;
        $story->size = $photo->getSize();
        $story->bearcap_token = str_random(64);
        $story->expires_at = now()->addMinutes(1440);
        $story->save();

        $url = $story->path;

        $res = [
            'code' => 200,
            'msg'  => 'Successfully added',
            'media_id' => (string) $story->id,
            'media_url' => url(Storage::url($url)) . '?v=' . time(),
            'media_type' => $story->type
        ];

        return $res;
    }

    public function publish(Request $request)
    {
        abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

        $this->validate($request, [
            'media_id' => 'required',
            'duration' => 'required|integer|min:0|max:30',
            'can_reply' => 'required|boolean',
            'can_react' => 'required|boolean'
        ]);

        $id = $request->input('media_id');
        $user = $request->user();
        $story = Story::whereProfileId($user->profile_id)
            ->findOrFail($id);

        $story->active = true;
        $story->duration = $request->input('duration', 10);
        $story->can_reply = $request->input('can_reply');
        $story->can_react = $request->input('can_react');
        $story->save();

        StoryService::delLatest($story->profile_id);
        StoryFanout::dispatch($story)->onQueue('story');
        StoryService::addRotateQueue($story->id);

        return [
            'code' => 200,
            'msg'  => 'Successfully published',
        ];
    }

    public function delete(Request $request, $id)
    {
        abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

        $user = $request->user();

        $story = Story::whereProfileId($user->profile_id)
            ->findOrFail($id);
        $story->active = false;
        $story->save();

        StoryDelete::dispatch($story)->onQueue('story');

        return [
            'code' => 200,
            'msg'  => 'Successfully deleted'
        ];
    }

    public function viewed(Request $request)
    {
        abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

        $this->validate($request, [
            'id'    => 'required|min:1',
        ]);
        $id = $request->input('id');

        $authed = $request->user()->profile;

        $story = Story::with('profile')
            ->findOrFail($id);
        $exp = $story->expires_at;

        $profile = $story->profile;

        if($story->profile_id == $authed->id) {
            return [];
        }

        $publicOnly = (bool) $profile->followedBy($authed);
        abort_if(!$publicOnly, 403);

        $v = StoryView::firstOrCreate([
            'story_id' => $id,
            'profile_id' => $authed->id
        ]);

        if($v->wasRecentlyCreated) {
            Story::findOrFail($story->id)->increment('view_count');

            if($story->local == false) {
                StoryViewDeliver::dispatch($story, $authed)->onQueue('story');
            }
        }

        Cache::forget('stories:recent:by_id:' . $authed->id);
        StoryService::addSeen($authed->id, $story->id);
        return ['code' => 200];
    }

    public function comment(Request $request)
    {
        abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);
        $this->validate($request, [
            'sid' => 'required',
            'caption' => 'required|string'
        ]);
        $pid = $request->user()->profile_id;
        $text = $request->input('caption');

        $story = Story::findOrFail($request->input('sid'));

        abort_if(!$story->can_reply, 422);

        $status = new Status;
        $status->type = 'story:reply';
        $status->profile_id = $pid;
        $status->caption = $text;
        $status->rendered = $text;
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $story->profile_id;
        $status->entities = json_encode([
            'story_id' => $story->id
        ]);
        $status->save();

        $dm = new DirectMessage;
        $dm->to_id = $story->profile_id;
        $dm->from_id = $pid;
        $dm->type = 'story:comment';
        $dm->status_id = $status->id;
        $dm->meta = json_encode([
            'story_username' => $story->profile->username,
            'story_actor_username' => $request->user()->username,
            'story_id' => $story->id,
            'story_media_url' => url(Storage::url($story->path)),
            'caption' => $text
        ]);
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $story->profile_id,
                'from_id' => $pid
            ],
            [
                'type' => 'story:comment',
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => false
            ]
        );

        if($story->local) {
            $n = new Notification;
            $n->profile_id = $dm->to_id;
            $n->actor_id = $dm->from_id;
            $n->item_id = $dm->id;
            $n->item_type = 'App\DirectMessage';
            $n->action = 'story:comment';
            $n->save();
        } else {
            StoryReplyDeliver::dispatch($story, $status)->onQueue('story');
        }

        return [
            'code' => 200,
            'msg'  => 'Sent!'
        ];
    }

    protected function storeMedia($photo, $user)
    {
        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if(in_array($photo->getMimeType(), [
            'image/jpeg',
            'image/png',
            'video/mp4'
        ]) == false) {
            abort(400, 'Invalid media type');
            return;
        }

        $storagePath = MediaPathService::story($user->profile);
        $path = $photo->storePubliclyAs($storagePath, Str::random(random_int(2, 12)) . '_' . Str::random(random_int(32, 35)) . '_' . Str::random(random_int(1, 14)) . '.' . $photo->extension());
        return $path;
    }

    public function viewers(Request $request)
    {
        abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

        $this->validate($request, [
            'sid' => 'required|string|min:1|max:50'
        ]);

        $pid = $request->user()->profile_id;
        $sid = $request->input('sid');

        $story = Story::whereProfileId($pid)
            ->whereActive(true)
            ->findOrFail($sid);

        $viewers = StoryView::whereStoryId($story->id)
            ->orderByDesc('id')
            ->cursorPaginate(10);

        return StoryViewResource::collection($viewers);
    }
}
