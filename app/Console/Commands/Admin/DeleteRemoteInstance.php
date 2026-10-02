<?php

namespace App\Console\Commands\Admin;

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\StatusPipeline\RemoteStatusDelete;
use App\Models\Instance;
use App\Models\Profile;
use App\Models\Status;
use App\Services\InstanceService;
use Illuminate\Console\Command;

class DeleteRemoteInstance extends Command
{
    protected $signature = 'app:delete-remote-instance
        {domain : The remote instance domain to purge}
        {--block : Ban the instance so it stays defederated after the purge}
        {--force : Skip the confirmation prompt}
        {--queue=adelete : Queue to dispatch the per-profile delete jobs on}';

    protected $description = 'Delete a remote instance and every trace of it: all remote accounts on the domain and their posts, comments, media and interactions';

    public function handle(): int
    {
        $domain = $this->normalizeDomain((string) $this->argument('domain'));

        if ($domain === '') {
            $this->error('A domain is required.');

            return self::FAILURE;
        }

        if ($domain === strtolower((string) config('pixelfed.domain.app'))) {
            $this->error('Refusing to delete the local instance domain.');

            return self::FAILURE;
        }

        $profileCount = Profile::whereDomain($domain)->whereNotNull('domain')->count();
        $statusCount = Status::whereIn('profile_id', Profile::whereDomain($domain)->select('id'))->count();
        $instance = Instance::whereDomain($domain)->first();

        if ($profileCount === 0 && ! $instance) {
            $this->warn("Nothing found for '{$domain}'.");

            return self::SUCCESS;
        }

        $this->table(['Domain', 'Remote accounts', 'Statuses', 'Instance row'], [[
            $domain,
            number_format($profileCount),
            number_format($statusCount),
            $instance ? 'yes' : 'no',
        ]]);

        if ($this->option('block')) {
            $this->line('The instance will be banned (defederated) and its record kept.');
        } else {
            $this->line('The instance record will be removed entirely.');
        }

        if (! $this->option('force') && ! $this->confirmByDomain($domain)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        // Ban first (if requested) so content stops re-federating mid-purge.
        if ($this->option('block')) {
            $this->banInstance($domain, $instance);
        }

        $queue = (string) $this->option('queue');
        $dispatched = 0;

        Profile::whereDomain($domain)
            ->whereNotNull('domain')
            ->chunkById(100, function ($profiles) use ($queue, &$dispatched) {
                foreach ($profiles as $profile) {
                    DeleteRemoteProfilePipeline::dispatch($profile)->onQueue($queue);
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} account delete job(s) on the '{$queue}' queue.");

        // Sweep any orphaned statuses that still carry the domain but whose
        // profile is already gone, so no content is left behind.
        $orphans = 0;
        Status::whereNotNull('uri')
            ->whereDoesntHave('profile')
            ->where('uri', 'like', 'https://'.$domain.'/%')
            ->chunkById(100, function ($statuses) use (&$orphans) {
                foreach ($statuses as $status) {
                    RemoteStatusDelete::dispatch($status)->onQueue('delete');
                    $orphans++;
                }
            });

        if ($orphans > 0) {
            $this->info("Dispatched {$orphans} orphaned status delete job(s).");
        }

        // Remove the instance record entirely unless we are keeping it banned.
        if (! $this->option('block') && $instance) {
            $instance->delete();
            InstanceService::refresh();
            $this->info('Removed the instance record.');
        }

        $this->info("Purge of '{$domain}' queued. It may take a while to drain.");

        return self::SUCCESS;
    }

    private function banInstance(string $domain, ?Instance $instance): void
    {
        $instance = $instance ?: new Instance(['domain' => $domain]);
        $instance->domain = $domain;
        $instance->banned = true;
        $instance->save();
        InstanceService::refresh();
        $this->info("Banned '{$domain}'.");
    }

    private function confirmByDomain(string $domain): bool
    {
        $this->warn('This permanently deletes every remote account on this domain and all of their content. It cannot be undone.');
        $answer = $this->ask("Type the domain ({$domain}) to confirm");

        return is_string($answer) && strtolower(trim($answer)) === $domain;
    }

    private function normalizeDomain(string $input): string
    {
        $input = trim($input);

        if (str_contains($input, '://')) {
            $input = (string) parse_url($input, PHP_URL_HOST);
        }

        return strtolower(trim($input, " \t\n\r\0\x0B/@"));
    }
}
