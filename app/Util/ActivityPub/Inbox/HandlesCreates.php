<?php

namespace App\Util\ActivityPub\Inbox;

use App\Federation\Handlers\DirectMessageHandler;
use App\Federation\Validators\DirectMessageValidator;
use App\Jobs\StatusPipeline\RemoteReplyResolvePipeline;
use App\Models\PollVote;
use App\Models\Profile;
use App\Models\Status;
use App\Services\FollowerService;
use App\Services\PollService;
use App\Util\ActivityPub\Helpers;

trait HandlesCreates
{
    public function handleCreateActivity(): void
    {
        $activity = $this->payload['object'];

        if ($this->isContentFilteredBySpam($activity)) {
            return;
        }

        $actor = $this->validateAndFetchActor($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (! isset($activity['to'])) {
            return;
        }

        if ($activity['type'] == 'Question') {
            return;
        }

        // A poll vote is addressed to the poll author alone, which is also
        // the shape of a direct message, so it has to be recognised first.
        // Votes are tallies: never a DM, never a comment.
        if ($activity['type'] == 'Note' && $this->isPollVoteObject($activity)) {
            if (
                is_string($activity['inReplyTo']) &&
                Helpers::validateLocalUrl($activity['inReplyTo'])
            ) {
                $this->handlePollVote();
            }

            return;
        }

        // Anything addressed only to people is a direct message, whether it
        // names one of them or several. It stops here even when it cannot be
        // stored: the paths below file every non-public Note as
        // followers-only, which would show it to the sender's followers.
        if (DirectMessageValidator::isDirect($activity, $actor)) {
            $this->handleDirectMessage($actor);

            return;
        }

        if ($activity['type'] == 'Note' && ! empty($activity['inReplyTo'])) {
            $this->handleNoteReply();
        } elseif ($activity['type'] == 'Note' && ! empty($activity['attachment'])) {
            if (! $this->verifyNoteAttachment()) {
                return;
            }
            $this->handleNoteCreate();
        }
    }

    /**
     * Handle a delivered reply.
     *
     * The object is stored from the signed delivery, not re-fetched. Fetching
     * loses every reply our instance actor cannot read (followers-only
     * replies to local posts), and the old fetch used object.url, which is an
     * HTML permalink on most software and an array on some.
     *
     * A reply is only ever stored with its parent attached. If the parent
     * cannot be resolved right now the delivery is parked in
     * RemoteReplyResolvePipeline and retried, never written as a top-level
     * status.
     */
    public function handleNoteReply(): void
    {
        $object = $this->payload['object'];

        $actorUrl = Helpers::pluckval($this->payload['actor'] ?? null);
        if (! is_string($actorUrl)) {
            return;
        }

        $actor = $this->validateAndFetchActor($actorUrl);
        if (! $actor || $actor->domain == null) {
            return;
        }

        $id = Helpers::pluckval($object['id'] ?? null);
        if (! is_string($id) || ! Helpers::validateUrl($id)) {
            return;
        }

        if (Helpers::findExistingStatus($id)) {
            return;
        }

        if (! $this->deliveredObjectIsTrusted($object, $actor, $id)) {
            // Not provably authored by the sender. Ask the origin, by id.
            Helpers::statusFirstOrFetch($id, true);

            return;
        }

        $published = Helpers::pluckval($object['published'] ?? null);
        if (! is_string($published) || ! Helpers::validateTimestamp($published)) {
            return;
        }

        $resolution = Helpers::resolveReplyParent($object, $actor);

        if ($resolution['state'] === Helpers::REPLY_PARENT_UNRESOLVED) {
            RemoteReplyResolvePipeline::park($object, $actor);

            return;
        }

        if (! Helpers::replyParentAllowsStore($resolution)) {
            return;
        }

        Helpers::storeStatus($id, $actor, $object);
    }

    /**
     * A delivered object can be stored as-is only when the sender is its
     * author: attributedTo is exactly the activity actor, the object id lives
     * on the actor's host, and, when the signing key maps to a known profile,
     * that profile is the actor. InboxValidator only binds these by host.
     */
    protected function deliveredObjectIsTrusted(array $object, Profile $actor, string $id): bool
    {
        $attributedTo = $object['attributedTo'] ?? null;

        if (! is_string($attributedTo) && ! is_array($attributedTo)) {
            return false;
        }

        $author = Helpers::extractAttributedTo($attributedTo);

        if (! is_string($author) || $author !== $actor->remote_url) {
            return false;
        }

        $idHost = parse_url($id, PHP_URL_HOST);
        $actorHost = parse_url((string) $actor->remote_url, PHP_URL_HOST);

        if (
            ! is_string($idHost) ||
            ! is_string($actorHost) ||
            strcasecmp($idHost, $actorHost) !== 0
        ) {
            return false;
        }

        $signer = $this->signingProfile();

        return ! $signer || (string) $signer->id === (string) $actor->id;
    }

    /**
     * A poll vote: a Note that names an option and says nothing else.
     */
    protected function isPollVoteObject(array $object): bool
    {
        return isset($object['inReplyTo'], $object['name']) &&
            ! isset($object['content']) &&
            ! isset($object['attachment']);
    }

    public function handlePollCreate(): void
    {
        $activity = $this->payload['object'];
        $actor = $this->validateAndFetchActor($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }
        $url = $activity['url'] ?? $activity['id'];
        Helpers::statusFirstOrFetch($url);
    }

    public function handleNoteCreate(): void
    {
        $activity = $this->payload['object'];
        $actor = $this->validateAndFetchActor($this->payload['actor']);
        if (! $actor || $actor->domain == null) {
            return;
        }

        if (
            isset($activity['inReplyTo']) &&
            isset($activity['name']) &&
            ! isset($activity['content']) &&
            ! isset($activity['attachment']) &&
            Helpers::validateLocalUrl($activity['inReplyTo'])
        ) {
            $this->handlePollVote();

            return;
        }

        if ($actor->followers_count == 0) {
            if (config('federation.activitypub.ingest.store_notes_without_followers')) {
                // allowed — continue
            } elseif (FollowerService::followerCount($actor->id, true) == 0) {
                return;
            }
        }

        $hasUrl = isset($activity['url']);
        $url = $activity['url'] ?? $activity['id'];

        if ($hasUrl) {
            if (Status::whereUri($url)->exists()) {
                return;
            }
        } else {
            if (Status::whereObjectUrl($url)->exists()) {
                return;
            }
        }

        $id = Helpers::pluckval($activity['id'] ?? null);

        if (! is_string($id) || ! Helpers::validateUrl($id)) {
            return;
        }

        // Same rule as replies: only store the delivered object when the
        // sender is provably its author. Otherwise an actor could claim
        // another server's object id and squat its unique object_url.
        if (! $this->deliveredObjectIsTrusted($activity, $actor, $id)) {
            Helpers::statusFirstOrFetch($id);

            return;
        }

        Helpers::storeStatus($url, $actor, $activity);
    }

    public function handlePollVote(): void
    {
        $activity = $this->payload['object'];
        $actor = $this->validateAndFetchActor($this->payload['actor']);

        if (! $actor) {
            return;
        }

        $status = Helpers::statusFetch($activity['inReplyTo']);

        if (! $status) {
            return;
        }

        $poll = $status->poll;

        if (! $poll) {
            return;
        }

        if (now()->gt($poll->expires_at)) {
            return;
        }

        $choices = $poll->poll_options;
        $choice = array_search($activity['name'], $choices);

        if ($choice === false) {
            return;
        }

        if (PollVote::whereStatusId($status->id)->whereProfileId($actor->id)->exists()) {
            return;
        }

        $vote = new PollVote;
        $vote->status_id = $status->id;
        $vote->profile_id = $actor->id;
        $vote->poll_id = $poll->id;
        $vote->choice = $choice;
        $vote->uri = $activity['id'] ?? null;
        $vote->save();

        $tallies = $poll->cached_tallies;
        $tallies[$choice] += 1;
        $poll->cached_tallies = $tallies;
        $poll->votes_count = array_sum($tallies);
        $poll->save();

        PollService::del($status->id);
    }

    /**
     * Hand a direct Note to the direct message handler.
     */
    public function handleDirectMessage(Profile $actor): void
    {
        $object = $this->payload['object'];

        $id = Helpers::pluckval($object['id'] ?? null);

        if (! is_string($id) || ! Helpers::validateUrl($id)) {
            return;
        }

        // Only the author can deliver their own message. A direct message is
        // never fetched to double check: it is not readable by anyone else.
        if (! $this->deliveredObjectIsTrusted($object, $actor, $id)) {
            return;
        }

        app(DirectMessageHandler::class)->handleCreate($object, $actor);
    }

    /**
     * Check if content matches autospam live filters.
     */
    protected function isContentFilteredBySpam(array $activity): bool
    {
        if (! config('autospam.live_filters.enabled')) {
            return false;
        }

        $filters = config('autospam.live_filters.filters');
        if (empty($filters) || ! isset($activity['content']) || empty($activity['content']) || strlen($filters) <= 3) {
            return false;
        }

        $filters = array_map('trim', explode(',', $filters));
        $content = strtolower($activity['content']);

        foreach ($filters as $filter) {
            $filter = trim(strtolower($filter));
            if (! $filter || ! strlen($filter)) {
                continue;
            }
            if (str_contains($content, $filter)) {
                return true;
            }
        }

        return false;
    }
}
