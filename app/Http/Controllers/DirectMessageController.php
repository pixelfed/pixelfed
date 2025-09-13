<?php

namespace App\Http\Controllers;

use App\DirectMessage;
use App\Jobs\DirectPipeline\DirectDeletePipeline;
use App\Jobs\DirectPipeline\DirectDeliverPipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Media;
use App\Models\Conversation;
use App\Notification;
use App\Profile;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\MediaService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use App\Services\UserRoleService;
use App\Services\UserStorageService;
use App\Services\WebfingerService;
use App\Status;
use App\UserFilter;
use App\Util\ActivityPub\Helpers;
use App\Util\Lexer\Autolink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DirectMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function browse(Request $request)
    {
        $this->validate($request, [
            'a' => 'nullable|string|in:inbox,sent,filtered',
            'page' => 'nullable|integer|min:1|max:99',
        ]);

        $user = $request->user();
        if ($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id)) {
            return [];
        }

        $profile = $user->profile_id;
        $action = $request->input('a', 'inbox');
        $page = $request->input('page', 1);
        $limit = 8;
        $offset = ($page - 1) * $limit;

        $baseQuery = DirectMessage::select(
            'id', 'type', 'to_id', 'from_id', 'status_id',
            'is_hidden', 'meta', 'created_at', 'read_at'
        )->with(['author', 'status', 'recipient']);

        if (config('database.default') == 'pgsql') {
            $query = match ($action) {
                'inbox' => $baseQuery->whereToId($profile)
                    ->whereIsHidden(false)
                    ->orderBy('created_at', 'desc'),
                'sent' => $baseQuery->whereFromId($profile)
                    ->orderBy('created_at', 'desc'),
                'filtered' => $baseQuery->whereToId($profile)
                    ->whereIsHidden(true)
                    ->orderBy('created_at', 'desc'),
                default => throw new \InvalidArgumentException('Invalid action')
            };

            $dms = $query->offset($offset)
                ->limit($limit)
                ->get();

            $dms = $action === 'sent' ?
                   $dms->unique('to_id') :
                   $dms->unique('from_id');
        } else {
            $query = match ($action) {
                'inbox' => $baseQuery->whereToId($profile)
                    ->whereIsHidden(false)
                    ->groupBy('from_id', 'id', 'type', 'to_id', 'status_id',
                        'is_hidden', 'meta', 'created_at', 'read_at')
                    ->orderBy('created_at', 'desc'),
                'sent' => $baseQuery->whereFromId($profile)
                    ->groupBy('to_id', 'id', 'type', 'from_id', 'status_id',
                        'is_hidden', 'meta', 'created_at', 'read_at')
                    ->orderBy('created_at', 'desc'),
                'filtered' => $baseQuery->whereToId($profile)
                    ->whereIsHidden(true)
                    ->groupBy('from_id', 'id', 'type', 'to_id', 'status_id',
                        'is_hidden', 'meta', 'created_at', 'read_at')
                    ->orderBy('created_at', 'desc'),
                default => throw new \InvalidArgumentException('Invalid action')
            };

            $dms = $query->offset($offset)
                ->limit($limit)
                ->get();
        }

        $mappedDms = $dms->map(function ($r) use ($action) {
            if ($action === 'sent') {
                return [
                    'id' => (string) $r->to_id,
                    'name' => $r->recipient->name,
                    'username' => $r->recipient->username,
                    'avatar' => $r->recipient->avatarUrl(),
                    'url' => $r->recipient->url(),
                    'isLocal' => (bool) ! $r->recipient->domain,
                    'domain' => $r->recipient->domain,
                    'timeAgo' => $r->created_at->diffForHumans(null, true, true),
                    'lastMessage' => $r->status->caption,
                    'messages' => [],
                ];
            }

            return [
                'id' => (string) $r->from_id,
                'name' => $r->author->name,
                'username' => $r->author->username,
                'avatar' => $r->author->avatarUrl(),
                'url' => $r->author->url(),
                'isLocal' => (bool) ! $r->author->domain,
                'domain' => $r->author->domain,
                'timeAgo' => $r->created_at->diffForHumans(null, true, true),
                'lastMessage' => $r->status->caption,
                'messages' => [],
            ];
        });

        return response()->json($mappedDms->values());
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'to_id' => 'required',
            'message' => 'required|string|min:1|max:500',
            'type' => 'required|in:text,emoji',
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id), 403, 'Invalid permissions for this action');
        if (! $user->is_admin) {
            if ((bool) ! config_cache('instance.allow_new_account_dms')) {
                abort_if($user->created_at->gt(now()->subHours(72)), 400, 'You need to wait a bit before you can DM another account');
            }
        }
        $profile = $user->profile;
        $recipient = Profile::where('id', '!=', $profile->id)->findOrFail($request->input('to_id'));

        abort_if(in_array($profile->id, $recipient->blockedIds()->toArray()), 403);
        $msg = $request->input('message');

        if ((! $recipient->domain && $recipient->user->settings->public_dm == false) || $recipient->is_private) {
            if ($recipient->follows($profile) == true) {
                $hidden = false;
            } else {
                $hidden = true;
            }
        } else {
            $hidden = false;
        }

        $status = new Status;
        $status->profile_id = $profile->id;
        $status->caption = $msg;
        $status->visibility = 'direct';
        $status->scope = 'direct';
        $status->in_reply_to_profile_id = $recipient->id;
        $status->save();

        $dm = new DirectMessage;
        $dm->to_id = $recipient->id;
        $dm->from_id = $profile->id;
        $dm->status_id = $status->id;
        $dm->is_hidden = $hidden;
        $dm->type = $request->input('type');
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $recipient->id,
                'from_id' => $profile->id,
            ],
            [
                'type' => $dm->type,
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => $hidden,
            ]
        );

        if (filter_var($msg, FILTER_VALIDATE_URL)) {
            if (Helpers::validateUrl($msg)) {
                $dm->type = 'link';
                $dm->meta = [
                    'domain' => parse_url($msg, PHP_URL_HOST),
                    'local' => parse_url($msg, PHP_URL_HOST) ==
                    parse_url(config('app.url'), PHP_URL_HOST),
                ];
                $dm->save();
            }
        }

        $nf = UserFilter::whereUserId($recipient->id)
            ->whereFilterableId($profile->id)
            ->whereFilterableType('App\Profile')
            ->whereFilterType('dm.mute')
            ->exists();

        if ($recipient->domain == null && $hidden == false && ! $nf) {
            $notification = new Notification;
            $notification->profile_id = $recipient->id;
            $notification->actor_id = $profile->id;
            $notification->action = 'dm';
            $notification->item_id = $dm->id;
            $notification->item_type = "App\DirectMessage";
            $notification->save();
        }

        if ($recipient->domain) {
            $this->remoteDeliver($dm);
        }

        $res = [
            'id' => (string) $dm->id,
            'isAuthor' => $profile->id == $dm->from_id,
            'reportId' => (string) $dm->status_id,
            'hidden' => (bool) $dm->is_hidden,
            'type' => $dm->type,
            'text' => $dm->status->caption,
            'media' => null,
            'timeAgo' => $dm->created_at->diffForHumans(null, null, true),
            'seen' => $dm->read_at != null,
            'meta' => $dm->meta,
        ];

        return response()->json($res);
    }

    public function thread(Request $request)
    {
        $this->validate($request, [
            'pid' => 'required',
            'max_id' => 'sometimes|integer',
            'min_id' => 'sometimes|integer',
        ]);

        $user = $request->user();
        abort_if(
            $user->has_roles && ! UserRoleService::can('can-direct-message', $user->id),
            403,
            'Invalid permissions for this action'
        );

        $uid = $user->profile_id;
        $pid = $request->input('pid');
        $max_id = $request->input('max_id');
        $min_id = $request->input('min_id');

        $profile = Profile::findOrFail($pid);

        $query = DirectMessage::select(
            'id',
            'is_hidden',
            'from_id',
            'to_id',
            'type',
            'status_id',
            'meta',
            'created_at',
            'read_at'
        )->with(['status']);

        if ($min_id) {
            $res = $query->where('id', '>', $min_id)
                ->where(function ($query) use ($pid, $uid) {
                    $query->where('from_id', $pid)->where('to_id', $uid);
                })->orWhere(function ($query) use ($pid, $uid) {
                    $query->where('from_id', $uid)->where('to_id', $pid);
                })
                ->orderBy('id', 'asc')
                ->take(8)
                ->get()
                ->reverse();
        } elseif ($max_id) {
            $res = $query->where('id', '<', $max_id)
                ->where(function ($query) use ($pid, $uid) {
                    $query->where('from_id', $pid)->where('to_id', $uid);
                })->orWhere(function ($query) use ($pid, $uid) {
                    $query->where('from_id', $uid)->where('to_id', $pid);
                })
                ->orderBy('id', 'desc')
                ->take(8)
                ->get();
        } else {
            $res = $query->where(function ($query) use ($pid, $uid) {
                $query->where('from_id', $pid)->where('to_id', $uid);
            })->orWhere(function ($query) use ($pid, $uid) {
                $query->where('from_id', $uid)->where('to_id', $pid);
            })
                ->orderBy('id', 'desc')
                ->take(8)
                ->get();
        }

        $messages = $res->filter(function ($message) {
            return $message && $message->status;
        })->map(function ($message) use ($uid) {
            return [
                'id' => (string) $message->id,
                'hidden' => (bool) $message->is_hidden,
                'isAuthor' => $uid == $message->from_id,
                'type' => $message->type,
                'text' => $message->status->caption,
                'media' => $message->status->firstMedia() ? $message->status->firstMedia()->url() : null,
                'carousel' => MediaService::get($message->status_id),
                'created_at' => $message->created_at->format('c'),
                'timeAgo' => $message->created_at->diffForHumans(null, null, true),
                'seen' => $message->read_at != null,
                'reportId' => (string) $message->status_id,
                'meta' => is_string($message->meta) ? json_decode($message->meta, true) : $message->meta,
            ];
        })->values();

        $filters = UserFilterService::mutes($uid);

        return response()->json([
            'id' => (string) $profile->id,
            'name' => $profile->name,
            'username' => $profile->username,
            'avatar' => $profile->avatarUrl(),
            'url' => $profile->url(),
            'muted' => in_array($profile->id, $filters),
            'isLocal' => (bool) ! $profile->domain,
            'domain' => $profile->domain,
            'created_at' => $profile->created_at->format('c'),
            'updated_at' => $profile->updated_at->format('c'),
            'timeAgo' => $profile->created_at->diffForHumans(null, true, true),
            'lastMessage' => '',
            'messages' => $messages,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);

        $sid = $request->input('id');
        $pid = $request->user()->profile_id;

        $dm = DirectMessage::whereFromId($pid)
            ->whereStatusId($sid)
            ->firstOrFail();

        $status = Status::whereProfileId($pid)
            ->findOrFail($dm->status_id);

        $recipient = AccountService::get($dm->to_id);

        if (! $recipient) {
            return response('', 422);
        }

        if ($recipient['local'] == false) {
            $dmc = $dm;
            $this->remoteDelete($dmc);
        } else {
            StatusDelete::dispatch($status)->onQueue('high');
        }

        if (Conversation::whereStatusId($sid)->count()) {
            $latest = DirectMessage::where(['from_id' => $dm->from_id, 'to_id' => $dm->to_id])
                ->orWhere(['to_id' => $dm->from_id, 'from_id' => $dm->to_id])
                ->latest()
                ->first();

            if ($latest->status_id == $sid) {
                Conversation::where(['to_id' => $dm->from_id, 'from_id' => $dm->to_id])
                    ->update([
                        'updated_at' => $latest->updated_at,
                        'status_id' => $latest->status_id,
                        'type' => $latest->type,
                        'is_hidden' => false,
                    ]);

                Conversation::where(['to_id' => $dm->to_id, 'from_id' => $dm->from_id])
                    ->update([
                        'updated_at' => $latest->updated_at,
                        'status_id' => $latest->status_id,
                        'type' => $latest->type,
                        'is_hidden' => false,
                    ]);
            } else {
                Conversation::where([
                    'status_id' => $sid,
                    'to_id' => $dm->from_id,
                    'from_id' => $dm->to_id,
                ])->delete();

                Conversation::where([
                    'status_id' => $sid,
                    'from_id' => $dm->from_id,
                    'to_id' => $dm->to_id,
                ])->delete();
            }
        }

        StatusService::del($status->id, true);

        $status->forceDeleteQuietly();

        return [200];
    }

    public function get(Request $request, $id)
    {
        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id), 403, 'Invalid permissions for this action');

        $pid = $request->user()->profile_id;
        $dm = DirectMessage::whereStatusId($id)->firstOrFail();
        abort_if($pid !== $dm->to_id && $pid !== $dm->from_id, 404);

        return response()->json($dm, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function mediaUpload(Request $request)
    {
        $this->validate($request, [
            'file' => function () {
                return [
                    'required',
                    'mimetypes:'.config_cache('pixelfed.media_types'),
                    'max:'.config_cache('pixelfed.max_photo_size'),
                ];
            },
            'to_id' => 'required',
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id), 403, 'Invalid permissions for this action');
        $profile = $user->profile;
        $recipient = Profile::where('id', '!=', $profile->id)->findOrFail($request->input('to_id'));
        abort_if(in_array($profile->id, $recipient->blockedIds()->toArray()), 403);

        if ((! $recipient->domain && $recipient->user->settings->public_dm == false) || $recipient->is_private) {
            if ($recipient->follows($profile) == true) {
                $hidden = false;
            } else {
                $hidden = true;
            }
        } else {
            $hidden = false;
        }

        $accountSize = UserStorageService::get($user->id);
        abort_if($accountSize === -1, 403, 'Invalid request.');
        $photo = $request->file('file');
        $fileSize = $photo->getSize();
        $sizeInKbs = (int) ceil($fileSize / 1000);
        $updatedAccountSize = (int) $accountSize + (int) $sizeInKbs;

        if ((bool) config_cache('pixelfed.enforce_account_limit') == true) {
            $limit = (int) config_cache('pixelfed.max_account_size');
            if ($updatedAccountSize >= $limit) {
                abort(403, 'Account size limit reached.');
            }
        }

        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if (in_array($photo->getMimeType(), $mimes) == false) {
            abort(403, 'Invalid or unsupported mime type.');
        }

        $storagePath = MediaPathService::get($user, 2).Str::random(8);
        $path = $photo->storePublicly($storagePath);
        $hash = \hash_file('sha256', $photo);

        abort_if(MediaBlocklistService::exists($hash) == true, 451);

        $status = new Status;
        $status->profile_id = $profile->id;
        $status->caption = null;
        $status->visibility = 'direct';
        $status->scope = 'direct';
        $status->in_reply_to_profile_id = $recipient->id;
        $status->save();

        $media = new Media;
        $media->status_id = $status->id;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->mime = $photo->getMimeType();
        $media->caption = null;
        $media->filter_class = null;
        $media->filter_name = null;
        $media->save();

        $dm = new DirectMessage;
        $dm->to_id = $recipient->id;
        $dm->from_id = $profile->id;
        $dm->status_id = $status->id;
        $dm->type = array_first(explode('/', $media->mime)) == 'video' ? 'video' : 'photo';
        $dm->is_hidden = $hidden;
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $recipient->id,
                'from_id' => $profile->id,
            ],
            [
                'type' => $dm->type,
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => $hidden,
            ]
        );

        $user->storage_used = (int) $updatedAccountSize;
        $user->storage_used_updated_at = now();
        $user->save();

        if ($recipient->domain) {
            $this->remoteDeliver($dm);
        }

        return [
            'id' => $dm->id,
            'reportId' => (string) $dm->status_id,
            'type' => $dm->type,
            'url' => $media->url(),
        ];
    }

    public function composeLookup(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|string|min:2|max:50',
            'remote' => 'nullable',
        ]);

        $user = $request->user();
        if ($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id)) {
            return [];
        }

        $q = $request->input('q');
        $r = $request->input('remote', false);

        if ($r && ! Str::of($q)->contains('.')) {
            return [];
        }

        if ($r && Helpers::validateUrl($q)) {
            Helpers::profileFetch($q);
        }

        if (Str::of($q)->startsWith('@')) {
            if (strlen($q) < 3) {
                return [];
            }
            if (substr_count($q, '@') == 2) {
                WebfingerService::lookup($q);
            }
            $q = mb_substr($q, 1);
        }

        $blocked = UserFilter::whereFilterableType('App\Profile')
            ->whereFilterType('block')
            ->whereFilterableId($request->user()->profile_id)
            ->pluck('user_id');

        $blocked->push($request->user()->profile_id);

        $results = Profile::select('id', 'domain', 'username')
            ->whereNotIn('id', $blocked)
            ->where('username', 'like', '%'.$q.'%')
            ->orderBy('domain')
            ->limit(8)
            ->get()
            ->map(function ($r) {
                $acct = AccountService::get($r->id);

                return [
                    'local' => (bool) ! $r->domain,
                    'id' => (string) $r->id,
                    'name' => $r->username,
                    'privacy' => true,
                    'avatar' => $r->avatarUrl(),
                    'account' => $acct,
                ];
            });

        return $results;
    }

    public function composeMutuals(Request $request)
    {
        $user = $request->user();
        if ($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id)) {
            return [];
        }

        return response()->json(FollowerService::getMutualsWithProfiles($user->profile_id, 10));
    }

    public function read(Request $request)
    {
        $this->validate($request, [
            'pid' => 'required',
            'sid' => 'required',
        ]);

        $pid = $request->input('pid');
        $sid = $request->input('sid');
        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id), 403, 'Invalid permissions for this action');

        $dms = DirectMessage::whereToId($request->user()->profile_id)
            ->whereFromId($pid)
            ->where('status_id', '>=', $sid)
            ->get();

        $now = now();
        foreach ($dms as $dm) {
            $dm->read_at = $now;
            $dm->save();
        }

        return response()->json($dms->pluck('id'));
    }

    public function mute(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id), 403, 'Invalid permissions for this action');
        $fid = $request->input('id');
        $pid = $request->user()->profile_id;

        UserFilter::firstOrCreate(
            [
                'user_id' => $pid,
                'filterable_id' => $fid,
                'filterable_type' => 'App\Profile',
                'filter_type' => 'dm.mute',
            ]
        );

        return [200];
    }

    public function unmute(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id), 403, 'Invalid permissions for this action');

        $fid = $request->input('id');
        $pid = $request->user()->profile_id;

        $f = UserFilter::whereUserId($pid)
            ->whereFilterableId($fid)
            ->whereFilterableType('App\Profile')
            ->whereFilterType('dm.mute')
            ->firstOrFail();

        $f->delete();

        return [200];
    }

    public function remoteDeliver($dm)
    {
        $profile = $dm->author;
        $url = $dm->recipient->sharedInbox ?? $dm->recipient->inbox_url;
        $status = $dm->status;

        if (! $status) {
            return;
        }

        $tags = [
            [
                'type' => 'Mention',
                'href' => $dm->recipient->permalink(),
                'name' => $dm->recipient->emailUrl(),
            ],
        ];

        $content = $status->caption ? Autolink::create()->autolink($status->caption) : null;

        $body = [
            '@context' => [
                'https://w3id.org/security/v1',
                'https://www.w3.org/ns/activitystreams',
            ],
            'id' => $dm->status->permalink(),
            'type' => 'Create',
            'actor' => $dm->status->profile->permalink(),
            'published' => $dm->status->created_at->toAtomString(),
            'to' => [$dm->recipient->permalink()],
            'cc' => [],
            'object' => [
                'id' => $dm->status->url(),
                'type' => 'Note',
                'summary' => null,
                'content' => $content,
                'inReplyTo' => null,
                'published' => $dm->status->created_at->toAtomString(),
                'url' => $dm->status->url(),
                'attributedTo' => $dm->status->profile->permalink(),
                'to' => [$dm->recipient->permalink()],
                'cc' => [],
                'sensitive' => (bool) $dm->status->is_nsfw,
                'attachment' => $dm->status->media()->orderBy('order')->get()->map(function ($media) {
                    return [
                        'type' => $media->activityVerb(),
                        'mediaType' => $media->mime,
                        'url' => $media->url(),
                        'name' => $media->caption,
                    ];
                })->toArray(),
                'tag' => $tags,
            ],
        ];

        DirectDeliverPipeline::dispatch($profile, $url, $body)->onQueue('high');
    }

    public function remoteDelete($dm)
    {
        $profile = $dm->author;
        $url = $dm->recipient->sharedInbox ?? $dm->recipient->inbox_url;

        $body = [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
            ],
            'id' => $dm->status->permalink('#delete'),
            'to' => [
                'https://www.w3.org/ns/activitystreams#Public',
            ],
            'type' => 'Delete',
            'actor' => $dm->status->profile->permalink(),
            'object' => [
                'id' => $dm->status->url(),
                'type' => 'Tombstone',
            ],
        ];
        DirectDeletePipeline::dispatch($profile, $url, $body)->onQueue('high');
    }
}
