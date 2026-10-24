<?php

namespace App\Jobs\ProfilePipeline;

use App\Models\Profile;
use App\Services\InstanceService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * Discover (or refresh) the actor that owns an HTTP signature keyId.
 *
 * Signed GET requests are verified inside a web request, where fetching an
 * unknown key would let anyone pin a worker on a slow remote host. Unknown
 * signers are resolved here instead, so their next request can be verified.
 */
class SigningActorDiscoveryPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $keyId;

    public $timeout = 60;

    public $tries = 1;

    public $maxExceptions = 1;

    public function __construct(string $keyId)
    {
        $this->keyId = $keyId;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('ap:signing-actor:'.hash('sha256', $this->keyId)))->shared()->dontRelease()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $keyId = Helpers::validateUrl($this->keyId);

        if (! $keyId) {
            return;
        }

        $host = strtolower((string) parse_url($keyId, PHP_URL_HOST));

        if (
            $host === ''
            || Helpers::isLocalDomain($host)
            || in_array($host, InstanceService::getBannedDomains())
        ) {
            return;
        }

        $known = Profile::whereKeyId($keyId)
            ->whereNotNull('domain')
            ->first();

        if ($known) {
            if ($known->remote_url) {
                // Refreshes the stored public key when the profile is stale.
                Helpers::getOrFetchRemoteProfile($known->remote_url);
            }

            return;
        }

        $document = Helpers::fetchFromUrl(explode('#', $keyId, 2)[0]);

        if (! is_array($document)) {
            return;
        }

        $actorUrl = self::ownerOf($document, $keyId);

        if (
            ! $actorUrl
            || strtolower((string) parse_url($actorUrl, PHP_URL_HOST)) !== $host
        ) {
            return;
        }

        Helpers::profileFirstOrNew($actorUrl);
    }

    /**
     * The keyId may dereference to the actor itself or to a standalone key
     * document that points at its owner.
     *
     * @param  array<string, mixed>  $document
     */
    protected static function ownerOf(array $document, string $keyId): ?string
    {
        $key = $document['publicKey'] ?? null;

        if (is_array($key) && array_is_list($key)) {
            $key = collect($key)->first(
                fn ($item) => is_array($item) && ($item['id'] ?? null) === $keyId,
                $key[0] ?? null
            );
        }

        $owner = (is_array($key) ? ($key['owner'] ?? null) : null)
            ?? $document['owner']
            ?? $document['id']
            ?? null;

        if (is_array($owner)) {
            $owner = $owner['id'] ?? null;
        }

        return is_string($owner) && $owner !== '' ? $owner : null;
    }
}
