<?php

namespace App\Console\Commands\User;

use App\Models\Profile;
use App\Models\User;
use App\Services\AccountDeleteFederationService;
use App\Services\AccountRevocationService;
use Illuminate\Console\Command;
use RuntimeException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\table;

class UserAccountDelete extends Command
{
    protected $signature = 'app:user-account-delete
        {--concurrency= : Number of concurrent deliveries (default: federation.activitypub.delivery.concurrency)}
        {--chunk=500 : Number of inboxes to process per batch}
        {--attempts=2 : Max attempts for retryable failures}
        {--target= : Send to a single inbox URL for debugging}
        {--verbose-errors : Log each failure to console}
        {--dry-run : Build payload and audience, but do not send}';

    protected $description = 'Federate Account Deletion';

    public function handle(AccountDeleteFederationService $service): int
    {
        $user = $this->promptForDeletedUser();
        if (! $user instanceof User) {
            $this->error('No deleted user selected.');

            return self::FAILURE;
        }

        $profile = Profile::withTrashed()->find($user->profile_id);
        if (! $profile) {
            $this->error('Profile not found for selected user.');

            return self::FAILURE;
        }

        $this->showUserSummary($user);

        AccountRevocationService::revokeAll($user);

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

        try {
            $prepared = $service->prepare($profile);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $attempts = max(1, (int) $this->option('attempts'));
        $concurrency = $this->option('concurrency') !== null
            ? max(1, (int) $this->option('concurrency'))
            : null;

        $audience = $service->audience();
        $totalTargets = $audience->count();

        if ($this->option('dry-run')) {
            $this->line('Dry run only.');
            $this->line("Audience size: {$totalTargets}");
            $this->line("Chunk size: {$chunkSize}");
            $this->line("Attempts: {$attempts}");
            $this->line('Concurrency: '.($concurrency ?? 'config default'));
            $this->line("Digest: {$prepared['digest']}");
            $this->line("Key ID: {$prepared['key_id']}");
            $this->line($prepared['payload']);

            return self::SUCCESS;
        }

        if ($target = $this->option('target')) {
            return $this->sendDebug($service, $profile, $prepared, $target);
        }

        if ($totalTargets === 0) {
            $this->warn('No candidate shared inboxes found.');

            return self::SUCCESS;
        }

        $onFailure = $this->option('verbose-errors')
            ? function (string $url, string $reason, ?int $status): void {
                if ($status === null) {
                    $this->error("  [TRANSPORT] {$url} - {$reason}");

                    return;
                }

                $this->warn("  [{$status}] {$url} - {$reason}");
            }
        : null;

        $results = [
            'delivered' => 0,
            'skipped' => 0,
            'invalid' => [],
            'http_failed' => [],
            'retry_exhausted' => [],
        ];

        $bar = $this->output->createProgressBar($totalTargets);
        $bar->start();

        foreach ($audience->chunk($chunkSize) as $chunk) {
            $pending = $chunk->values()->all();
            $resolved = 0;

            for ($attempt = 1; $attempt <= $attempts && $pending !== []; $attempt++) {
                $batch = $service->deliver($profile, $prepared, $pending, $concurrency, $onFailure);

                $results['delivered'] += count($batch['delivered']);
                $results['skipped'] += count($batch['skipped']);
                $results['invalid'] += $batch['invalid'];
                $results['http_failed'] += $batch['http_failed'];

                $resolved += count($batch['delivered'])
                    + count($batch['skipped'])
                    + count($batch['invalid'])
                    + count($batch['http_failed']);

                $pending = array_keys($batch['retryable']);

                if ($attempt === $attempts) {
                    $results['retry_exhausted'] += $batch['retryable'];
                    $resolved += count($batch['retryable']);
                } elseif ($pending !== []) {
                    usleep(100_000);
                }
            }

            $bar->advance($resolved);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Delivered: {$results['delivered']}");
        $this->line("Skipped (host marked unavailable): {$results['skipped']}");
        $this->warn('Invalid inbox URLs: '.count($results['invalid']));
        $this->warn('HTTP failures: '.count($results['http_failed']));
        $this->warn('Transport/retry-exhausted failures: '.count($results['retry_exhausted']));

        return self::SUCCESS;
    }

    protected function promptForDeletedUser(): ?User
    {
        $id = search(
            label: 'Search for the account to delete by username',
            options: fn (string $value) => $value !== ''
                ? User::withTrashed()
                    ->whereIn('status', ['deleted', 'delete'])
                    ->where('username', 'like', "%{$value}%")
                    ->pluck('username', 'id')
                    ->all()
                : [],
            placeholder: 'john.appleseed',
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

    /**
     * @param  array{payload: string, digest: string, length: int, key_id: string}  $prepared
     */
    protected function sendDebug(
        AccountDeleteFederationService $service,
        Profile $profile,
        array $prepared,
        string $url
    ): int {
        $headers = $service->signedHeaders($profile, $prepared, $url);

        $this->info('Target: '.$url);
        $this->newLine();

        $this->info('Request headers:');
        foreach ($headers as $key => $value) {
            $this->line("  {$key}: {$value}");
        }
        $this->newLine();

        $this->info('Payload:');
        $this->line($prepared['payload']);
        $this->newLine();

        try {
            $response = $service->send($profile, $prepared, $url, 15, $headers);

            $status = $response->status();
            $body = $response->body();

            $this->info("Response status: {$status}");
            $this->newLine();

            $this->info('Response headers:');
            foreach ($response->headers() as $name => $values) {
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
}
