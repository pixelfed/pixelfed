<?php

namespace App\Observers;

use App\Jobs\PushNotificationPipeline\WebPushNotifyPipeline;
use App\Models\Follower;
use App\Models\FollowRequest;
use Illuminate\Support\Facades\Cache;

/**
 * Sends a Web Push when someone requests to follow a private local account.
 *
 * There is no notification of any kind for this today: a follow request
 * writes a follow_requests row and nothing else — no Notification record, no
 * Expo push — so nothing a client can observe short of polling the follow
 * requests list. This observer is the missing event.
 *
 * It also suppresses the redundant `follow` push that accepting a request
 * produces; see deleted() and
 * WebPushNotifyPipeline::followRequestMarkerKey().
 *
 * It hooks the model rather than the two call sites that create these rows
 * (HandlesFollows for a federated request, ApiV1Controller::accountFollowById
 * for a local one) because `created` fires once per genuine insert and so
 * covers both paths, including any added later, from one place.
 */
class FollowRequestObserver
{
    /**
     * Matching NotificationObserver: a queued job must not start before the
     * row it describes is committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the follow request "created" event.
     */
    public function created(FollowRequest $followRequest): void
    {
        $target = $followRequest->target;

        // A follow_requests row does not always mean someone wants to follow
        // *us*. accountFollowById writes one for our own pending follow of a
        // remote account too, and pushing for that would notify the wrong
        // person. A local target is what distinguishes the two — and a local
        // target is always a private one, since that is the only case in which
        // either call site creates a request rather than a Follower.
        if (! $target || $target->domain !== null) {
            return;
        }

        $actor = $followRequest->actor;

        if (! $actor) {
            return;
        }

        // No status id: a follow request has no post, so the client deep-links
        // to the requester's profile.
        WebPushNotifyPipeline::maybeDispatch($target->id, 'follow_request', $actor->username, $actor->id);
    }

    /**
     * Handle the follow request "deleted" event.
     *
     * A request row disappears in three ways, and they need different things:
     * accepted (a Follower row now exists), rejected by the target, or
     * cancelled by the requester — the last two indistinguishable from here,
     * and treated the same.
     *
     * Only an accept is followed by a `follow` push worth suppressing, so only
     * an accept keeps the marker, and then only for as long as that push
     * should take to arrive. A reject clears it outright: if the same person
     * is allowed to follow later, that is a genuinely new follower and must
     * notify.
     */
    public function deleted(FollowRequest $followRequest): void
    {
        $marker = WebPushNotifyPipeline::followRequestMarkerKey(
            $followRequest->following_id,
            $followRequest->follower_id
        );

        if (! Cache::has($marker)) {
            return;
        }

        $accepted = Follower::whereProfileId($followRequest->follower_id)
            ->whereFollowingId($followRequest->following_id)
            ->exists();

        if ($accepted) {
            Cache::put($marker, true, WebPushNotifyPipeline::FOLLOW_REQUEST_MARKER_ACCEPTED_TTL);

            return;
        }

        Cache::forget($marker);
    }
}
