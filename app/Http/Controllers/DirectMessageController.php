<?php

namespace App\Http\Controllers;

use App\Exceptions\DirectMessageException;
use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Media;
use App\Models\Profile;
use App\Services\AccountService;
use App\Services\DirectMessagePayloadService;
use App\Services\DirectMessageService;
use App\Services\FollowerService;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\MediaStorageService;
use App\Services\UserFilterService;
use App\Services\UserRoleService;
use App\Services\UserStorageService;
use App\Services\WebfingerService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The original one-to-one direct message endpoints.
 *
 * Threads here are keyed by the other person's profile id, which is what the
 * Blade UI and the older mobile app speak. They are thin wrappers around the
 * conversation model and only ever see one-to-one conversations. Anything new
 * should use DirectConversationController.
 */
class DirectMessageController extends Controller
{
    public function __construct(
        protected DirectMessageService $service,
        protected DirectMessagePayloadService $payloads
    ) {
        $this->middleware('auth');
    }

    public function browse(Request $request)
    {
        $this->validate($request, [
            'a' => 'nullable|string|in:inbox,sent,filtered',
            'page' => 'nullable|integer|min:1|max:99',
        ]);

        $user = $request->user();
        if (! $this->service->canUseDirectMessages($user)) {
            return [];
        }

        $pid = $user->profile_id;
        $action = $request->input('a', 'inbox');
        $limit = 8;
        $offset = ((int) $request->input('page', 1) - 1) * $limit;

        $rows = DmConversationParticipant::query()
            ->join('dm_conversations', 'dm_conversations.id', '=', 'dm_conversation_participants.conversation_id')
            ->where('dm_conversations.type', DmConversation::TYPE_DM)
            ->where('dm_conversation_participants.profile_id', $pid)
            ->whereNotNull('dm_conversation_participants.last_activity_at')
            ->whereNull('dm_conversation_participants.hidden_at')
            ->when($action === 'filtered', fn ($q) => $q->where('dm_conversation_participants.state', DmConversationParticipant::STATE_REQUEST))
            ->when($action !== 'filtered', fn ($q) => $q->where('dm_conversation_participants.state', DmConversationParticipant::STATE_ACTIVE))
            ->when($action === 'sent', fn ($q) => $q->where('dm_conversations.created_by_profile_id', $pid))
            ->orderByDesc('dm_conversation_participants.last_activity_at')
            ->offset($offset)
            ->limit($limit)
            ->get(['dm_conversation_participants.*', 'dm_conversations.last_message_id']);

        $others = DmConversationParticipant::whereIn('conversation_id', $rows->pluck('conversation_id'))
            ->where('profile_id', '!=', $pid)
            ->pluck('profile_id', 'conversation_id');

        $profiles = Profile::whereIn('id', $others->values())->get()->keyBy('id');
        $lastIds = $rows->pluck('last_message_id', 'conversation_id');
        $lastMessages = DmMessage::whereIn('id', $lastIds->filter())->get()->keyBy('id');

        $threads = $rows->map(function ($row) use ($others, $profiles, $lastIds, $lastMessages) {
            $other = $profiles->get($others->get($row->conversation_id));

            if (! $other) {
                return null;
            }

            $last = $lastMessages->get($lastIds->get($row->conversation_id));

            return [
                'id' => (string) $other->id,
                'name' => $other->name,
                'username' => $other->username,
                'avatar' => $other->avatarUrl(),
                'url' => $other->url(),
                'isLocal' => (bool) ! $other->domain,
                'domain' => $other->domain,
                'timeAgo' => $row->last_activity_at->diffForHumans(null, true, true),
                'lastMessage' => $last?->body,
                'messages' => [],
            ];
        })->filter()->values();

        return response()->json($threads);
    }

    public function create(Request $request): JsonResponse
    {
        $this->validate($request, [
            'to_id' => 'required',
            'message' => 'required|string|min:1|max:'.(int) config('dm.max_message_length'),
            'type' => 'required|in:text,emoji',
        ]);

        $user = $request->user();
        $this->authorizeSend($user);

        $profile = $user->profile;
        $recipient = Profile::where('id', '!=', $profile->id)->findOrFail($request->input('to_id'));

        abort_if(! $this->service->canMessage($profile, $recipient), 403);

        $conversation = $this->service->findOrCreateDm($profile, $recipient);

        $message = $this->service->sendMessage($conversation, $profile, [
            'body' => $request->input('message'),
            'type' => $request->input('type'),
        ]);

        return response()->json($this->payloads->legacyMessage(
            $message,
            $profile->id,
            $this->isRequest($conversation, $recipient->id)
        ));
    }

    public function thread(Request $request): JsonResponse
    {
        $this->validate($request, [
            'pid' => 'required',
            'max_id' => 'sometimes|integer',
            'min_id' => 'sometimes|integer',
        ]);

        $user = $request->user();
        abort_if(! $this->service->canUseDirectMessages($user), 403, 'Invalid permissions for this action');

        $uid = $user->profile_id;
        $profile = Profile::findOrFail($request->input('pid'));

        $conversation = $this->service->findDm($uid, $profile->id);
        $messages = collect();
        $muted = false;

        if ($conversation) {
            $viewer = $this->service->participant($conversation, $uid);
            $other = $this->service->participant($conversation, $profile->id);
            $muted = (bool) $viewer?->muted_at;
            $hidden = (bool) ($viewer?->isRequest() || $other?->isRequest());

            $query = DmMessage::with('media')
                ->where('conversation_id', $conversation->id)
                ->whereNotIn('profile_id', $this->payloads->blockedIds($uid) ?: [0]);

            if ($request->filled('min_id')) {
                $res = $query->where('id', '>', $request->input('min_id'))
                    ->orderBy('id')
                    ->take(8)
                    ->get()
                    ->reverse();
            } else {
                if ($request->filled('max_id')) {
                    $query->where('id', '<', $request->input('max_id'));
                }

                $res = $query->orderByDesc('id')->take(8)->get();
            }

            $messages = $res->map(fn (DmMessage $message) => $this->payloads->legacyMessage(
                $message,
                $uid,
                $hidden,
                $other?->last_read_message_id,
                $viewer?->last_read_message_id
            ))->values();
        }

        return response()->json([
            'id' => (string) $profile->id,
            'name' => $profile->name,
            'username' => $profile->username,
            'avatar' => $profile->avatarUrl(),
            'url' => $profile->url(),
            'muted' => $muted,
            'isLocal' => (bool) ! $profile->domain,
            'domain' => $profile->domain,
            'created_at' => $profile->created_at->format('c'),
            'updated_at' => $profile->updated_at->format('c'),
            'timeAgo' => $profile->created_at->diffForHumans(null, true, true),
            'lastMessage' => '',
            'messages' => $messages,
            'conversation_id' => $conversation ? (string) $conversation->id : null,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
        ]);

        $message = DmMessage::where('profile_id', $request->user()->profile_id)
            ->findOrFail($request->input('id'));

        $this->service->deleteMessage($message);

        return [200];
    }

    public function mediaUpload(Request $request): array
    {
        $this->validate($request, [
            'file' => [
                'required',
                'mimetypes:'.config_cache('pixelfed.media_types'),
                'max:'.config_cache('pixelfed.max_photo_size'),
            ],
            'to_id' => 'required',
            'message' => 'sometimes|nullable|string|max:'.(int) config('dm.max_message_length'),
        ]);

        $user = $request->user();
        $this->authorizeSend($user);

        $profile = $user->profile;
        $recipient = Profile::where('id', '!=', $profile->id)->findOrFail($request->input('to_id'));

        abort_if(! $this->service->canMessage($profile, $recipient), 403);

        $accountSize = UserStorageService::get($user->id);
        abort_if($accountSize === -1, 403, 'Invalid request.');
        $photo = $request->file('file');
        $fileSize = $photo->getSize();
        $sizeInKbs = (int) ceil($fileSize / 1000);
        $updatedAccountSize = (int) $accountSize + (int) $sizeInKbs;

        if ((bool) config_cache('pixelfed.enforce_account_limit') === true) {
            $limit = (int) config_cache('pixelfed.max_account_size');
            if ($updatedAccountSize >= $limit) {
                abort(403, 'Account size limit reached.');
            }
        }

        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if (in_array($photo->getMimeType(), $mimes) === false) {
            abort(403, 'Invalid or unsupported mime type.');
        }

        // Check the blocklist against the temp upload BEFORE storing, so a
        // blocked upload never leaves an orphaned file on disk (media:gc only
        // reaps files that have a Media row).
        $hash = \hash_file('sha256', $photo->getRealPath());
        abort_if(MediaBlocklistService::exists($hash) == true, 451);

        $conversation = $this->service->findOrCreateDm($profile, $recipient);

        $storagePath = MediaPathService::get($user, 2).Str::random(8);
        $path = $photo->storePublicly($storagePath);

        $media = new Media;
        $media->status_id = null;
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

        try {
            $message = $this->service->sendMessage($conversation, $profile, [
                'body' => $request->input('message'),
                'media' => collect([$media]),
            ]);
        } catch (DirectMessageException $e) {
            MediaStorageService::delete($media, true);

            throw $e;
        }

        UserStorageService::increaseStorageUsed($user->id, $fileSize);

        return [
            'id' => (string) $message->id,
            'reportId' => (string) $message->id,
            'type' => $message->type,
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

        if ($r && ! Str::contains($q, '.')) {
            return [];
        }

        if ($r && Helpers::validateUrl($q)) {
            Helpers::profileFetch($q);
        }

        if (Str::startsWith($q, '@')) {
            if (strlen($q) < 3) {
                return [];
            }
            if (substr_count($q, '@') === 2) {
                WebfingerService::lookup($q);
            }
            $q = mb_substr($q, 1);
        }

        $blocked = UserFilterService::searchExcludedProfileIds($request->user()->profile_id);

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

    public function read(Request $request): JsonResponse
    {
        $this->validate($request, [
            'pid' => 'required',
            'sid' => 'required',
        ]);

        $user = $request->user();
        abort_if(! $this->service->canUseDirectMessages($user), 403, 'Invalid permissions for this action');

        $conversation = $this->service->findDm($user->profile_id, (int) $request->input('pid'));
        $participant = $conversation ? $this->service->participant($conversation, $user->profile_id) : null;

        if (! $participant) {
            return response()->json([]);
        }

        $ids = DmMessage::where('conversation_id', $conversation->id)
            ->where('profile_id', $request->input('pid'))
            ->where('id', '>=', $request->input('sid'))
            ->when($participant->last_read_message_id, fn ($q) => $q->where('id', '>', $participant->last_read_message_id))
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            $this->service->markRead($participant, (int) $ids->max());
        }

        return response()->json($ids->map(fn ($id) => (string) $id)->values());
    }

    public function mute(Request $request): array
    {
        $this->toggleMute($request, true);

        return [200];
    }

    public function unmute(Request $request): array
    {
        $this->toggleMute($request, false);

        return [200];
    }

    protected function toggleMute(Request $request, bool $muted): void
    {
        $this->validate($request, [
            'id' => 'required',
        ]);

        $user = $request->user();
        abort_if(! $this->service->canUseDirectMessages($user), 403, 'Invalid permissions for this action');

        $other = Profile::where('id', '!=', $user->profile_id)->findOrFail($request->input('id'));

        $conversation = $muted
            ? $this->service->findOrCreateDm($user->profile, $other)
            : $this->service->findDm($user->profile_id, $other->id);

        abort_if(! $conversation, 404);

        $this->service->setMuted($this->service->participant($conversation, $user->profile_id), $muted);
    }

    protected function authorizeSend($user): void
    {
        abort_if(! $this->service->canUseDirectMessages($user), 403, 'Invalid permissions for this action');
        abort_if(! $this->service->canInitiateConversation($user), 400, 'You need to wait a bit before you can DM another account');
    }

    protected function isRequest(DmConversation $conversation, int $profileId): bool
    {
        return (bool) $this->service->participant($conversation, $profileId)?->isRequest();
    }
}
