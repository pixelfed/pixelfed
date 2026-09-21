<?php

namespace App\Jobs\InboxPipeline\Concerns;

use App\Util\ActivityPub\Helpers;

/**
 * First contact with a remote actor gets more than one chance.
 *
 * The inbox endpoints answer 2xx before the signature is checked, so the
 * sender considers the activity delivered and will never send it again. If
 * the actor could not be fetched because of a timeout, a 5xx or similar,
 * dropping the job loses the activity for good. For most types that is an
 * annoyance, for a QuoteRequest it leaves the quote pending forever.
 *
 * Instead the job is released and tried again later. Only temporary fetch
 * failures qualify: bad signatures, banned domains and actors that answer
 * 401/403/404/410 are dropped on the spot, exactly as before.
 *
 * The using job needs $tries = count(ACTOR_RETRY_DELAYS) + 1. Keep
 * $maxExceptions = 1 so a thrown exception still fails the job at once.
 */
trait RetriesWhenActorUnavailable
{
    /**
     * Seconds to wait before each retry. Every delay has to be longer than
     * Helpers::FETCH_NEGATIVE_TTL, otherwise the retry only finds the
     * cached failure instead of asking the remote again. Same opening
     * cadence as RemoteReplyResolvePipeline::BACKOFF.
     *
     * @var array<int, int>
     */
    public const ACTOR_RETRY_DELAYS = [180, 900, 3600];

    /**
     * Set by verifySignature() when the signing actor is unknown and could
     * not be fetched for a reason that looks temporary.
     */
    protected bool $actorUnavailable = false;

    protected function markActorUnavailable(mixed $actorUrl): void
    {
        $this->actorUnavailable = is_string($actorUrl)
            && Helpers::fetchFailedTransiently($actorUrl);
    }

    /**
     * Release the job for another attempt if the actor was temporarily
     * unreachable and there are attempts left. Returns true when released.
     */
    protected function retryLaterIfActorUnavailable(): bool
    {
        if (! $this->actorUnavailable) {
            return false;
        }

        $attempt = $this->attempts();

        if ($attempt > count(self::ACTOR_RETRY_DELAYS)) {
            return false;
        }

        $this->release(self::ACTOR_RETRY_DELAYS[$attempt - 1]);

        return true;
    }
}
