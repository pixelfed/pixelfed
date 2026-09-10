<?php

namespace App\Console\Commands\User;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;

class ReclaimUsername extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reclaim-username';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force delete a user and their profile to reclaim a username';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $username = search(
            label: 'What username would you like to reclaim?',
            options: fn (string $search) => strlen($search) > 0 ? $this->getUsernameOptions($search) : [],
            required: true
        );

        $user = User::whereUsername($username)->withTrashed()->first();

        if (! $user) {
            $this->error("No user found with username: {$username}");

            return Command::FAILURE;
        }

        if ($user->delete_after === null || $user->status !== 'deleted') {
            $this->error("Cannot reclaim an active account: {$username}");

            return Command::FAILURE;
        }

        $confirm = confirm(
            label: "Are you sure you want to force delete user and profile with username: {$username}?",
            default: false
        );

        if (! $confirm) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        // Force-delete the user's OWN profiles, scoped by user_id rather than a
        // bare username match, so we never destroy a different user's profile
        // that happens to share the username.
        Profile::whereUserId($user->id)
            ->withTrashed()
            ->get()
            ->each
            ->forceDelete();
        $this->info("Profile {$username} has been force deleted.");

        $user->forceDelete();
        $this->info("User {$username} has been force deleted.");

        // A same-username orphan profile (not linked to this user) can survive
        // and continue to claim the username. Verify the username is genuinely
        // free before reporting success.
        $survivors = Profile::whereUsername($username)->withTrashed()->get();

        if ($survivors->isNotEmpty()) {
            $this->warn("Found {$survivors->count()} surviving profile(s) with username '{$username}' (IDs: {$survivors->pluck('id')->implode(', ')}).");
            $this->error('Username could not be fully reclaimed. Manual cleanup of surviving profiles is required.');

            return Command::FAILURE;
        }

        $this->info('Username reclaimed successfully!');

        return Command::SUCCESS;
    }

    private function getUsernameOptions(string $search = ''): array
    {
        return User::where('username', 'like', "{$search}%")
            ->withTrashed()
            ->whereNotNull('delete_after')
            ->take(10)
            ->pluck('username')
            ->toArray();
    }
}
