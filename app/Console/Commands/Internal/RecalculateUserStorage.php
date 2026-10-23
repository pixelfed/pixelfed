<?php

namespace App\Console\Commands\Internal;

use App\Models\User;
use App\Services\UserStorageService;
use Illuminate\Console\Command;

class RecalculateUserStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:storage:recalculate
        {--user= : Only recalculate for the given user id}
        {--stale= : Only recalculate users whose storage_used_updated_at is older than N hours (or null)}
        {--chunk=200 : Number of users to process per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate users.storage_used from actual media, fixing stale account size limits';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $userId = $this->option('user');

        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User {$userId} not found");

                return 1;
            }
            $updated = UserStorageService::recalculateUpdateStorageUsed($user->id);
            $this->info("Recalculated storage for user {$user->id}: {$updated} KB");

            return 0;
        }

        $query = User::whereNull('status');

        $stale = $this->option('stale');
        if ($stale !== null && $stale !== '') {
            $cutoff = now()->subHours((int) $stale);
            $query->where(function ($q) use ($cutoff) {
                $q->whereNull('storage_used_updated_at')
                    ->orWhere('storage_used_updated_at', '<', $cutoff);
            });
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No users to recalculate');

            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $chunk = max(1, (int) $this->option('chunk'));

        $query->chunkById($chunk, function ($users) use ($bar) {
            foreach ($users as $user) {
                UserStorageService::recalculateUpdateStorageUsed($user->id);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->line('');
        $this->info("Recalculated storage for {$total} users");

        return 0;
    }
}
