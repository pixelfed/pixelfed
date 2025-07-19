<?php

namespace App\Console\Commands;

use App\Profile;
use App\User;
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
        $profile = Profile::whereUsername($username)->withTrashed()->first();

        if (! $user && ! $profile) {
            $this->error("No user or profile found with username: {$username}");

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

        if ($user) {
            $user->forceDelete();
            $this->info("User {$username} has been force deleted.");
        }

        if ($profile) {
            $profile->forceDelete();
            $this->info("Profile {$username} has been force deleted.");
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
