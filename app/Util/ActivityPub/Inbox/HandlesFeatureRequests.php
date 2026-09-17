<?php

namespace App\Util\ActivityPub\Inbox;

use App\Models\Profile;
use App\Services\FeaturedCollectionService;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Support\Facades\Log;

trait HandlesFeatureRequests
{
    public function handleFeatureRequestActivity(): void
    {
        $requestUrl = $this->payload['id'];

        $actor = $this->featureRequestActor();

        if (! $actor) {
            return;
        }

        if (! $this->hostsMatch($requestUrl, $actor->remote_url)) {
            return;
        }

        $collectionUrl = $this->featureRequestUrlField('instrument');

        if (! $collectionUrl || ! $this->hostsMatch($collectionUrl, $actor->remote_url)) {
            return;
        }

        $target = $this->featureRequestTarget();

        if (! $target) {
            return;
        }

        $existing = FeaturedCollectionService::find($target, $collectionUrl);

        if ($existing && $existing->isRevoked()) {
            FeaturedCollectionService::sendReject($target, $actor, $requestUrl);

            return;
        }

        if ($existing && $existing->isApproved()) {
            FeaturedCollectionService::sendAccept($existing, $requestUrl);

            return;
        }

        if (! FeaturedCollectionService::canFeature($target, $actor)) {
            FeaturedCollectionService::sendReject($target, $actor, $requestUrl);

            return;
        }

        $collection = FeaturedCollectionService::fetchCollection($collectionUrl, $actor);

        if (! $collection) {
            Log::info('HandlesFeatureRequests: collection unfetchable or not owned by requester', [
                'actor_id' => $actor->id,
                'profile_id' => $target->id,
                'collection' => $collectionUrl,
            ]);

            FeaturedCollectionService::sendReject($target, $actor, $requestUrl);

            return;
        }

        $auth = FeaturedCollectionService::authorize(
            $target,
            $actor,
            $collection['id'],
            $requestUrl,
            $collection['name']
        );

        FeaturedCollectionService::sendAccept($auth, $requestUrl);
    }

    /**
     * The requester. FeatureRequest usually has no `actor` property, so
     * fall back to the profile that owns the HTTP signature key. The inbox
     * validator already verified the signature against that key.
     */
    protected function featureRequestActor(): ?Profile
    {
        $signer = $this->featureRequestSigner();

        $claimed = $this->featureRequestUrlField('actor');

        if ($claimed) {
            $actor = $this->validateAndFetchActor($claimed);

            if (! $actor || $actor->domain === null) {
                return null;
            }

            // A claimed actor must be the one that signed the request.
            if ($signer && $signer->id !== $actor->id) {
                return null;
            }

            return $actor;
        }

        if (! $signer || $signer->domain === null) {
            return null;
        }

        return $signer;
    }

    protected function featureRequestSigner(): ?Profile
    {
        $signature = $this->headers['signature'] ?? null;

        if (is_array($signature)) {
            $signature = $signature[0] ?? null;
        }

        if (! is_string($signature) || $signature === '') {
            return null;
        }

        $data = HttpSignature::parseSignatureHeader($signature);

        if (isset($data['error']) || empty($data['keyId'])) {
            return null;
        }

        $keyId = Helpers::validateUrl($data['keyId']);

        if (! $keyId) {
            return null;
        }

        return Profile::whereKeyId($keyId)->first();
    }

    /**
     * The local profile being featured. Must be local and active.
     */
    protected function featureRequestTarget(): ?Profile
    {
        $objectUrl = $this->featureRequestUrlField('object');

        if (! $objectUrl) {
            return null;
        }

        $localUrl = Helpers::validateLocalUrl($objectUrl);

        if (! $localUrl) {
            return null;
        }

        $target = Helpers::profileFetch($localUrl);

        if (! $target || $target->domain !== null || $target->status !== null || $target->deleted_at) {
            return null;
        }

        return $target;
    }

    /**
     * Pluck a URL out of a payload field that may be a string, an array of
     * strings, or an embedded object with an id.
     */
    protected function featureRequestUrlField(string $field): ?string
    {
        $value = Helpers::pluckval($this->payload[$field] ?? null);

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
