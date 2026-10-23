<?php

namespace App\Http\Controllers;

use App\Exceptions\DirectMessageException;
use App\Models\DmConversation;
use App\Models\DmConversationParticipant;
use App\Models\DmMessage;
use App\Models\Profile;
use App\Services\DirectMessagePayloadService;
use App\Services\DirectMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectConversationController extends Controller
{
    public function __construct(
        protected DirectMessageService $service,
        protected DirectMessagePayloadService $payloads
    ) {
        $this->middleware('auth');
    }

    /**
     * GET /api/v1.1/direct/conversations
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeRequest($request, 'read');

        $this->validate($request, [
            'filter' => 'sometimes|string|in:primary,requests,hidden',
            'limit' => 'sometimes|integer|min:1|max:40',
            'cursor' => 'sometimes|nullable|string',
        ]);

        $pid = $request->user()->profile_id;
        $filter = $request->input('filter', 'primary');

        $page = $this->inboxQuery($pid, $filter)
            ->with('conversation')
            ->cursorPaginate((int) $request->input('limit', 20));

        return response()->json([
            'data' => $this->payloads->conversations(collect($page->items()), $pid),
            'meta' => [
                'filter' => $filter,
                'next_cursor' => $page->nextCursor()?->encode(),
                'prev_cursor' => $page->previousCursor()?->encode(),
            ],
        ]);
    }

    /**
     * GET /api/v1.1/direct/unread_count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $this->authorizeRequest($request, 'read');

        $pid = $request->user()->profile_id;

        return response()->json([
            'primary' => (int) $this->inboxQuery($pid, 'primary')
                ->whereNull('muted_at')
                ->where('unread_count', '>', 0)
                ->count(),
            'requests' => (int) $this->inboxQuery($pid, 'requests')->count(),
        ]);
    }

    /**
     * POST /api/v1.1/direct/conversations
     *
     * Find the conversation with these people, or start it. One recipient is
     * a 1:1 chat, several are a group.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeRequest($request, 'write');

        $this->validate($request, [
            'recipient_ids' => 'required|array|min:1|max:'.max(1, (int) config('dm.groups.max_participants') - 1),
            'recipient_ids.*' => 'required|integer|min:1|distinct',
        ]);

        $user = $request->user();
        $sender = $user->profile;

        $ids = collect($request->input('recipient_ids'))->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === $sender->id);
        $recipients = Profile::whereIn('id', $ids)->whereNull('status')->get();

        if ($recipients->isEmpty() || $recipients->count() !== $ids->count()) {
            throw new DirectMessageException('One or more recipients could not be found.', 404);
        }

        $hash = DmConversation::participantsHash($recipients->pluck('id')->push($sender->id)->all());
        $exists = DmConversation::where('participants_hash', $hash)->exists();

        if (! $exists) {
            if (! $this->service->canInitiateConversation($user)) {
                throw new DirectMessageException('You need to wait a bit before you can DM another account', 400);
            }

            foreach ($recipients as $recipient) {
                if ($recipients->count() === 1 && ! $this->service->canMessage($sender, $recipient)) {
                    throw new DirectMessageException('You cannot message this account.', 403);
                }

                // In a group, someone who blocks the sender is still added and
                // simply never sees their messages. The sender blocking one
                // of their own picks is a mistake worth pointing out.
                if ($recipients->count() > 1 && $this->service->isBlockedBy($sender, $recipient)) {
                    throw new DirectMessageException('You have blocked one of these accounts.', 422);
                }

                if ($recipient->domain !== null && ! config('federation.activitypub.enabled')) {
                    throw new DirectMessageException('You cannot message this account.', 403);
                }
            }
        }

        $conversation = $this->service->findOrCreateConversation($sender, $recipients);
        $participant = $this->service->participant($conversation, $sender->id);

        if ($participant->hasLeft()) {
            $participant->state = DmConversationParticipant::STATE_ACTIVE;
            $participant->save();
        }

        return response()->json(
            $this->payloads->conversation($conversation, $participant, null, $this->lastVisibleMessage($conversation, $sender->id), $sender->id),
            $exists ? 200 : 201
        );
    }

    /**
     * GET /api/v1.1/direct/conversations/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $this->authorizeRequest($request, 'read');

        [$conversation, $participant] = $this->resolve($request, $id);
        $pid = $request->user()->profile_id;

        return response()->json(
            $this->payloads->conversation($conversation, $participant, null, $this->lastVisibleMessage($conversation, $pid), $pid)
        );
    }

    /**
     * GET /api/v1.1/direct/conversations/{id}/messages
     *
     * Newest first. `max_id` pages back in time, `min_id` returns what
     * arrived since, which is what a client polls with.
     */
    public function messages(Request $request, $id): JsonResponse
    {
        $this->authorizeRequest($request, 'read');

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1|max:50',
            'max_id' => 'sometimes|integer|min:1',
            'min_id' => 'sometimes|integer|min:1',
        ]);

        [$conversation] = $this->resolve($request, $id);

        $pid = $request->user()->profile_id;
        $limit = (int) $request->input('limit', 20);

        $query = DmMessage::with('media')
            ->where('conversation_id', $conversation->id)
            ->whereNotIn('profile_id', $this->payloads->blockedIds($pid) ?: [0]);

        if ($request->filled('min_id')) {
            $messages = $query->where('id', '>', $request->input('min_id'))
                ->orderBy('id')
                ->limit($limit + 1)
                ->get();

            $hasMore = $messages->count() > $limit;
            $messages = $messages->take($limit)->reverse()->values();
        } else {
            if ($request->filled('max_id')) {
                $query->where('id', '<', $request->input('max_id'));
            }

            $messages = $query->orderByDesc('id')->limit($limit + 1)->get();

            $hasMore = $messages->count() > $limit;
            $messages = $messages->take($limit)->values();
        }

        return response()->json([
            'data' => $this->payloads->messages($messages, $pid),
            'meta' => [
                'has_more' => $hasMore,
                'newest_id' => $messages->isNotEmpty() ? (string) $messages->first()->id : null,
                'oldest_id' => $messages->isNotEmpty() ? (string) $messages->last()->id : null,
            ],
        ]);
    }

    /**
     * POST /api/v1.1/direct/conversations/{id}/messages
     *
     * Text, media, or both in a single message. Media is uploaded first
     * through /api/v2/media and referenced here by id.
     */
    public function send(Request $request, $id): JsonResponse
    {
        $this->authorizeRequest($request, 'write');

        $this->validate($request, [
            'message' => 'nullable|required_without:media_ids|string|max:'.(int) config('dm.max_message_length'),
            'media_ids' => 'nullable|required_without:message|array|max:'.(int) config('dm.max_media'),
            'media_ids.*' => 'integer|min:1|distinct',
            'in_reply_to_id' => 'sometimes|nullable|integer|min:1',
            'sensitive' => 'sometimes|boolean',
            'type' => 'sometimes|string|in:text,emoji',
        ]);

        [$conversation] = $this->resolve($request, $id);

        $user = $request->user();

        $message = $this->service->sendMessage($conversation, $user->profile, [
            'body' => $request->input('message'),
            'type' => $request->input('type'),
            'media' => $this->service->attachableMedia($user, $request->input('media_ids', []) ?? []),
            'in_reply_to_id' => $request->input('in_reply_to_id'),
            'is_sensitive' => $request->boolean('sensitive'),
        ]);

        return response()->json($this->payloads->message($message->load('media'), $user->profile_id), 201);
    }

    /**
     * DELETE /api/v1.1/direct/conversations/{id}/messages/{messageId}
     */
    public function deleteMessage(Request $request, $id, $messageId): JsonResponse
    {
        $this->authorizeRequest($request, 'write');

        [$conversation] = $this->resolve($request, $id);

        $message = DmMessage::where('conversation_id', $conversation->id)
            ->where('profile_id', $request->user()->profile_id)
            ->findOrFail($messageId);

        $this->service->deleteMessage($message);

        return response()->json(['deleted' => true]);
    }

    /**
     * POST /api/v1.1/direct/conversations/{id}/read
     */
    public function read(Request $request, $id): JsonResponse
    {
        $this->authorizeRequest($request, 'write');

        $this->validate($request, [
            'message_id' => 'sometimes|nullable|integer|min:1',
        ]);

        [, $participant] = $this->resolve($request, $id);

        $this->service->markRead($participant, $request->filled('message_id') ? (int) $request->input('message_id') : null);

        return $this->state($participant);
    }

    /**
     * POST /api/v1.1/direct/conversations/{id}/accept
     */
    public function accept(Request $request, $id): JsonResponse
    {
        $this->authorizeRequest($request, 'write');

        [, $participant] = $this->resolve($request, $id);

        $this->service->accept($participant);

        return $this->state($participant);
    }

    public function mute(Request $request, $id): JsonResponse
    {
        return $this->toggle($request, $id, fn ($participant) => $this->service->setMuted($participant, true));
    }

    public function unmute(Request $request, $id): JsonResponse
    {
        return $this->toggle($request, $id, fn ($participant) => $this->service->setMuted($participant, false));
    }

    public function hide(Request $request, $id): JsonResponse
    {
        return $this->toggle($request, $id, fn ($participant) => $this->service->setHidden($participant, true));
    }

    public function unhide(Request $request, $id): JsonResponse
    {
        return $this->toggle($request, $id, fn ($participant) => $this->service->setHidden($participant, false));
    }

    /**
     * POST /api/v1.1/direct/conversations/{id}/leave
     */
    public function leave(Request $request, $id): JsonResponse
    {
        $this->authorizeRequest($request, 'write');

        [$conversation, $participant] = $this->resolve($request, $id);

        $this->service->leave($conversation, $participant);

        return $this->state($participant);
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    protected function toggle(Request $request, $id, callable $action): JsonResponse
    {
        $this->authorizeRequest($request, 'write');

        [, $participant] = $this->resolve($request, $id);

        $action($participant);

        return $this->state($participant);
    }

    protected function state(DmConversationParticipant $participant): JsonResponse
    {
        return response()->json([
            'id' => (string) $participant->conversation_id,
            'state' => $participant->state,
            'unread_count' => (int) $participant->unread_count,
            'muted' => $participant->muted_at !== null,
            'hidden' => $participant->hidden_at !== null,
            'last_read_message_id' => $participant->last_read_message_id ? (string) $participant->last_read_message_id : null,
        ]);
    }

    /**
     * OAuth tokens need the matching scope. Session and cookie auth have no
     * token and are allowed through, like the rest of the web API.
     */
    protected function authorizeRequest(Request $request, string $scope): void
    {
        $user = $request->user();

        abort_if(! $user, 403);

        if ($user->token() && ! $user->tokenCan($scope)) {
            abort(403, 'Missing required scope: '.$scope);
        }

        abort_if(! $this->service->canUseDirectMessages($user), 403, 'Invalid permissions for this action');
    }

    /**
     * @return array{0: DmConversation, 1: DmConversationParticipant}
     */
    protected function resolve(Request $request, $id): array
    {
        abort_unless(is_numeric($id), 404);

        $found = $this->service->conversationFor($id, $request->user()->profile_id);

        abort_if(! $found, 404);

        return $found;
    }

    /**
     * The viewer's conversations for one tab of the inbox. A conversation
     * shows up once something in it is visible to the viewer.
     */
    protected function inboxQuery(int $profileId, string $filter)
    {
        $query = DmConversationParticipant::where('profile_id', $profileId)
            ->whereNotNull('last_activity_at');

        match ($filter) {
            'requests' => $query->where('state', DmConversationParticipant::STATE_REQUEST)->whereNull('hidden_at'),
            'hidden' => $query->whereIn('state', [DmConversationParticipant::STATE_ACTIVE, DmConversationParticipant::STATE_REQUEST])->whereNotNull('hidden_at'),
            default => $query->where('state', DmConversationParticipant::STATE_ACTIVE)->whereNull('hidden_at'),
        };

        return $query->orderByDesc('last_activity_at')->orderByDesc('id');
    }

    protected function lastVisibleMessage(DmConversation $conversation, int $viewerId): ?DmMessage
    {
        if (! $conversation->last_message_id) {
            return null;
        }

        $message = DmMessage::with('media')->find($conversation->last_message_id);

        if ($message && in_array((int) $message->profile_id, $this->payloads->blockedIds($viewerId), true)) {
            return null;
        }

        return $message;
    }
}
