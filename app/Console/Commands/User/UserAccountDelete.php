<?php

namespace App\Console\Commands\User;

use App\Instance;
use App\Profile;
use App\User;
use App\Util\ActivityPub\HttpSignature;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use JsonException;
use Psr\Http\Message\ResponseInterface;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\table;

class UserAccountDelete extends Command
{
    protected $signature = 'app:user-account-delete
        {--concurrency=50 : Number of concurrent deliveries}
        {--chunk=500 : Number of inbox rows to process per DB chunk}
        {--attempts=2 : Max attempts for retryable failures}
        {--target= : Send to a single inbox URL for debugging}
        {--verbose-errors : Log each failure to console}
        {--dry-run : Build payload and audience, but do not send}';

    protected $description = 'Federate Account Deletion';

    public function handle(): int
    {
        $user = $this->promptForDeletedUser();
        if (! $user) {
            $this->error('No deleted user selected.');

            return self::FAILURE;
        }

        $profile = Profile::withTrashed()->find($user->profile_id);
        if (! $profile) {
            $this->error('Profile not found for selected user.');

            return self::FAILURE;
        }

        $this->showUserSummary($user);

        $confirmed = confirm(
            label: 'Do you want to federate this account deletion?',
            default: false,
            yes: 'Proceed',
            no: 'Cancel',
            hint: 'This action is irreversible'
        );

        if (! $confirmed) {
            $this->warn('Aborting...');

            return self::FAILURE;
        }

        $activity = $this->buildDeleteActivity($profile);

        try {
            $payload = json_encode(
                $activity,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );

            $digest = base64_encode(hash('sha256', $payload, true));
            $payloadLen = strlen($payload);
        } catch (JsonException $e) {
            $this->error("Failed to encode delete payload: {$e->getMessage()}");

            return self::FAILURE;
        }

        $query = $this->sharedInboxQuery();

        $chunkSize = max(1, (int) $this->option('chunk'));
        $attempts = max(1, (int) $this->option('attempts'));
        $concurrency = max(1, (int) $this->option('concurrency'));

        $totalTargets = (clone $query)
            ->toBase()
            ->distinct()
            ->count('shared_inbox');

        $privateKey = $profile->private_key;

        if (empty($privateKey)) {
            $this->error('Profile private key has been wiped — cannot sign deletion activity.');

            return self::FAILURE;
        }

        $keyId = $profile->keyId();

        if (empty($keyId)) {
            $this->error('Profile key id has been wiped — cannot sign deletion activity.');

            return self::FAILURE;
        }

        try {
            $testHeaders = HttpSignature::signRawWithDigest(
                $privateKey,
                $keyId,
                config('app.url').'/inbox',
                $digest,
            );
            if (empty($testHeaders) || ! isset($testHeaders['Signature'])) {
                $this->error('Instance actor signing failed — run php artisan instance:actor');

                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Instance actor error: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('Dry run only.');
            $this->line("Audience size: {$totalTargets}");
            $this->line("Chunk size: {$chunkSize}");
            $this->line("Attempts: {$attempts}");
            $this->line("Concurrency: {$concurrency}");
            $this->line("Digest: {$digest}");
            $this->line("Key ID: {$keyId}");
            $this->line($payload);

            return self::SUCCESS;
        }

        if ($target = $this->option('target')) {
            return $this->sendDebug($target, $payload, $digest, $privateKey, $keyId);
        }

        if ($totalTargets === 0) {
            $this->warn('No candidate shared inboxes found.');

            return self::SUCCESS;
        }

        $client = $this->makeHttpClient();

        $results = [
            'delivered' => 0,
            'http_failed' => [],
            'transport_failed' => [],
            'retry_exhausted' => [],
        ];

        $bar = $this->output->createProgressBar($totalTargets);
        $bar->start();

        $query
            ->orderBy('shared_inbox')
            ->chunk($chunkSize, function ($instances) use (
                $client,
                $payload,
                $privateKey,
                $payloadLen,
                $keyId,
                $digest,
                $concurrency,
                $attempts,
                &$results,
                $bar
            ) {
                $urls = $instances
                    ->pluck('shared_inbox')
                    ->filter()
                    ->unique()
                    ->values();

                if ($urls->isEmpty()) {
                    return;
                }

                $pending = $urls;
                $terminalDelivered = 0;
                $terminalHttpFailed = [];
                $terminalTransportFailed = [];

                for ($attempt = 1; $attempt <= $attempts && $pending->isNotEmpty(); $attempt++) {
                    $batch = $this->sendBatch(
                        client: $client,
                        privateKey: $privateKey,
                        keyId: $keyId,
                        digest: $digest,
                        urls: $pending,
                        payload: $payload,
                        payloadLen: $payloadLen,
                        concurrency: $concurrency,
                        verboseErrors: $this->option('verbose-errors')
                    );

                    $terminalDelivered += count($batch['delivered']);
                    $terminalHttpFailed += $batch['http_failed'];

                    $pending = collect($batch['retryable']->keys())->values();

                    if ($attempt === $attempts && $pending->isNotEmpty()) {
                        foreach ($pending as $url) {
                            $terminalTransportFailed[$url] = $batch['retryable'][$url] ?? 'retry exhausted';
                        }
                    }

                    if ($attempt < $attempts && $pending->isNotEmpty()) {
                        usleep(100_000);
                    }
                }

                $results['delivered'] += $terminalDelivered;
                $results['http_failed'] += $terminalHttpFailed;
                $results['transport_failed'] += $terminalTransportFailed;
                $results['retry_exhausted'] += $terminalTransportFailed;

                $resolved = $terminalDelivered + count($terminalHttpFailed) + count($terminalTransportFailed);
                $bar->advance($resolved);
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Delivered: {$results['delivered']}");
        $this->warn('HTTP failures: '.count($results['http_failed']));
        $this->warn('Transport/retry-exhausted failures: '.count($results['transport_failed']));

        return self::SUCCESS;
    }

    protected function promptForDeletedUser(): ?User
    {
        $id = search(
            label: 'Search for the account to delete by username',
            placeholder: 'john.appleseed',
            options: fn (string $value) => strlen($value) > 0
                ? User::withTrashed()
                    ->whereStatus('deleted')
                    ->where('username', 'like', "%{$value}%")
                    ->pluck('username', 'id')
                    ->all()
                : [],
        );

        return User::withTrashed()->find($id);
    }

    protected function showUserSummary(User $user): void
    {
        table(
            ['Username', 'Name', 'Email', 'Created'],
            [[
                $user->username,
                $user->name,
                $user->email,
                (string) $user->created_at,
            ]]
        );
    }

    protected function buildDeleteActivity(Profile $profile): array
    {
        $actorId = $profile->permalink();

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actorId.'#delete',
            'type' => 'Delete',
            'actor' => $actorId,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'object' => $actorId,
        ];
    }

    protected function sharedInboxQuery()
    {
        return Instance::query()
            ->whereNotNull('shared_inbox')
            ->whereNotNull('nodeinfo_last_fetched')
            ->where('nodeinfo_last_fetched', '>', now()->subDays(30))
            ->select('shared_inbox')
            ->distinct();
    }

    protected function makeHttpClient(): Client
    {
        return new Client([
            'timeout' => 10.0,
            'connect_timeout' => 5.0,
            'http_errors' => false,
            'allow_redirects' => false,
            'version' => '1.1',
            'headers' => [
                'User-Agent' => 'Pixelfed ('.config('app.url').')',
                'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            ],
        ]);
    }

    protected function sendBatch(
        Client $client,
        string $privateKey,
        string $keyId,
        string $digest,
        Collection $urls,
        string $payload,
        int $payloadLen,
        int $concurrency,
        bool $verboseErrors = false
    ): array {

        $delivered = [];
        $httpFailed = [];
        $retryable = [];

        $requests = function () use ($urls, $privateKey, $keyId, $digest, $payload, $payloadLen) {
            foreach ($urls as $url) {
                $headers = HttpSignature::signRawWithDigest($privateKey, $keyId, $url, $digest);
                $headers['Content-Type'] = 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"';
                $headers['Content-Length'] = (string) $payloadLen;
                yield $url => new Request('POST', $url, $headers, $payload);
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => $concurrency,
            'fulfilled' => function (ResponseInterface $response, string $url) use (&$delivered, &$httpFailed, &$retryable, $verboseErrors) {
                $status = $response->getStatusCode();

                if ($status >= 200 && $status < 300) {
                    $delivered[$url] = $status;

                    return;
                }

                $body = mb_substr((string) $response->getBody(), 0, 500);

                if ($verboseErrors) {
                    $this->warn("  [{$status}] {$url} — {$body}");
                }

                if ($this->isRetryableStatus($status)) {
                    $retryable[$url] = "HTTP {$status}";

                    return;
                }

                $httpFailed[$url] = [
                    'status' => $status,
                    'body' => $body,
                ];
            },
            'rejected' => function ($reason, string $url) use (&$retryable, $verboseErrors) {
                $message = $reason instanceof \Throwable
                    ? $reason->getMessage()
                    : (string) $reason;

                if ($verboseErrors) {
                    $this->error("  [TRANSPORT] {$url} — {$message}");
                }

                $retryable[$url] = $message;
            },
        ]);

        $pool->promise()->wait();

        return [
            'delivered' => $delivered,
            'http_failed' => $httpFailed,
            'retryable' => collect($retryable),
        ];
    }

    protected function sendDebug(string $url, string $payload, string $digest, string $privateKey, string $keyId): int
    {
        $headers = HttpSignature::signRawWithDigest($privateKey, $keyId, $url, $digest);

        $headers['Content-Type'] = 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"';

        $this->info('Target: '.$url);
        $this->newLine();

        $this->info('Request headers:');
        foreach ($headers as $key => $value) {
            $this->line("  {$key}: {$value}");
        }
        $this->newLine();

        $this->info('Payload:');
        $this->line($payload);
        $this->newLine();

        $client = new Client([
            'timeout' => 15.0,
            'connect_timeout' => 5.0,
            'http_errors' => false,
            'allow_redirects' => false,
        ]);

        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'body' => $payload,
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            $this->info("Response status: {$status}");
            $this->newLine();

            $this->info('Response headers:');
            foreach ($response->getHeaders() as $name => $values) {
                $this->line("  {$name}: ".implode(', ', $values));
            }
            $this->newLine();

            $this->info('Response body:');
            $this->line($body ?: '(empty)');

            return $status >= 200 && $status < 300 ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Transport error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function isRetryableStatus(int $status): bool
    {
        return in_array($status, [408, 425, 429, 500, 502, 503, 504], true);
    }
}
