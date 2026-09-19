<?php

namespace App\Util\ActivityPub\Inbox;

use App\Models\Profile;
use App\Models\Status;
use App\Services\QuoteService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Log;

/**
 * FEP-044f: a remote actor asks permission to quote a local post.
 *
 * Anything we cannot attribute to a real remote actor and a quotable local
 * post is dropped silently. Once both are known, the answer is always an
 * explicit Accept (with a QuoteAuthorization stamp) or Reject.
 */
trait HandlesQuoteRequests
{
    public function handleQuoteRequestActivity(): void
    {
        $requestUrl = $this->payload['id'];

        $actor = $this->quoteRequestActor();

        if (! $actor) {
            return;
        }

        if (! $this->hostsMatch($requestUrl, $actor->remote_url)) {
            return;
        }

        $quoteUrl = $this->quoteRequestInstrumentUrl($actor);

        if (! $quoteUrl) {
            return;
        }

        $status = $this->quoteRequestTarget();

        if (! $status) {
            return;
        }

        $existing = QuoteService::find($status, $quoteUrl);

        if ($existing && ($existing->isRevoked() || $existing->actor_id !== $actor->id)) {
            QuoteService::sendReject($status, $actor, $quoteUrl, $requestUrl);

            return;
        }

        if ($existing && $existing->isApproved()) {
            QuoteService::sendAccept($existing, $requestUrl);

            return;
        }

        if (
            ! QuoteService::canQuote($status, $actor) ||
            ! $this->quoteRequestInstrumentMatches($actor, $status)
        ) {
            Log::info('HandlesQuoteRequests: rejected', [
                'actor_id' => $actor->id,
                'status_id' => $status->id,
                'quote' => $quoteUrl,
            ]);

            QuoteService::sendReject($status, $actor, $quoteUrl, $requestUrl);

            return;
        }

        $auth = QuoteService::authorize($status, $actor, $quoteUrl, $requestUrl);

        QuoteService::sendAccept($auth, $requestUrl);
    }

    /**
     * The requester. Must be a remote actor and must be the one that signed
     * the request. The user inbox only checks that the key and the actor
     * share a host, so the exact match is enforced here.
     */
    protected function quoteRequestActor(): ?Profile
    {
        $claimed = $this->quoteRequestUrlField($this->payload['actor'] ?? null);

        if (! $claimed) {
            return null;
        }

        $actor = $this->validateAndFetchActor($claimed);

        if (! $actor || $actor->domain === null) {
            return null;
        }

        $signer = $this->signingProfile();

        if ($signer && $signer->id !== $actor->id) {
            return null;
        }

        return $actor;
    }

    /**
     * The id of the quote post. `instrument` may be the bare id or the
     * inlined object. It has to live on the requester's host, and it is
     * returned exactly as sent because the stamp's `interactingObject` is
     * compared byte for byte by the quoting server.
     */
    protected function quoteRequestInstrumentUrl(Profile $actor): ?string
    {
        $raw = $this->quoteRequestNode($this->payload['instrument'] ?? null);

        if (is_array($raw)) {
            $raw = $raw['id'] ?? null;
        }

        if (! is_string($raw)) {
            return null;
        }

        $raw = trim($raw);

        if ($raw === '' || strlen($raw) > 500) {
            return null;
        }

        if (! Helpers::validateUrl($raw) || ! $this->hostsMatch($raw, $actor->remote_url)) {
            return null;
        }

        return $raw;
    }

    /**
     * When the quote post is inlined, make sure it is what the request
     * says it is: written by the requester, and quoting this post. A bare
     * id passes, the FEP only says we MAY inspect the instrument and the
     * requester's server is authoritative for its own objects either way.
     */
    protected function quoteRequestInstrumentMatches(Profile $actor, Status $status): bool
    {
        $instrument = $this->quoteRequestNode($this->payload['instrument'] ?? null);

        if (! is_array($instrument)) {
            return true;
        }

        if (isset($instrument['attributedTo'])) {
            $author = $this->quoteRequestUrlField($instrument['attributedTo']);

            if (! QuoteService::sameUrl($author, $actor->remote_url)) {
                return false;
            }
        }

        foreach (QuoteService::QUOTE_PROPERTIES as $property) {
            if (! isset($instrument[$property])) {
                continue;
            }

            $quoted = $this->quoteRequestUrlField($instrument[$property]);

            if (! $quoted || ! Helpers::validateLocalUrl($quoted)) {
                return false;
            }

            $found = Helpers::findExistingStatus($quoted);

            if (! $found || (int) $found->id !== (int) $status->id) {
                return false;
            }
        }

        return true;
    }

    /**
     * The local post being quoted. Anything that is not a public or
     * unlisted post by an active local account is ignored without a
     * Reject, so a request cannot be used to probe for private posts.
     */
    protected function quoteRequestTarget(): ?Status
    {
        $objectUrl = $this->quoteRequestUrlField($this->payload['object'] ?? null);

        if (! $objectUrl) {
            return null;
        }

        $localUrl = Helpers::validateLocalUrl($objectUrl);

        if (! $localUrl) {
            return null;
        }

        $status = Helpers::findExistingStatus($localUrl);

        if (! $status || ! QuoteService::isQuotable($status)) {
            return null;
        }

        return $status;
    }

    /**
     * Unwrap a JSON-LD value that may be a single node or a list of them.
     * Helpers::pluckval() is not used here because it calls head() on
     * embedded objects too, which returns their first property.
     *
     * @return string|array<string, mixed>|null
     */
    protected function quoteRequestNode(mixed $value): string|array|null
    {
        if (is_array($value) && array_is_list($value)) {
            $value = $value[0] ?? null;
        }

        return is_string($value) || is_array($value) ? $value : null;
    }

    /**
     * Pluck a URL out of a value that may be a string, an array of
     * strings, or an embedded object with an id.
     */
    protected function quoteRequestUrlField(mixed $value): ?string
    {
        $value = $this->quoteRequestNode($value);

        if (is_array($value)) {
            $value = $value['id'] ?? null;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $url = Helpers::validateUrl($value);

        return is_string($url) ? $url : null;
    }
}
