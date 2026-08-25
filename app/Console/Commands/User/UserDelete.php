<?php

namespace App\Console\Commands\User;

use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Profile;
use App\Services\AccountService;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

class UserDelete extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete {id} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete account';

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'id' => 'Which user ID or username should be deleted?',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->argument('id');
        $force = $this->option('force');

        $profiles = Profile::where('username', 'like', '%'.$id.'%')
            ->orWhere('user_id', $id)
            ->orWhere('id', $id)
            ->orderByDesc('followers_count')
            ->get();

        if (! $profiles || ! $profiles->count()) {
            $this->error('Invalid user id or username');

            return Command::FAILURE;
        }

        $selectedUsername = select(
            'Select the account',
            $profiles->map(function ($u) {
                return $u->username;
            })
        );

        $profile = Profile::whereUsername($selectedUsername)->first();

        if (! $profile) {
            $this->error('Invalid id or username');

            return Command::FAILURE;
        }

        $user = User::find($profile->user_id);

        if (! $user) {
            $this->error('Could not find associated user account.');

            return Command::FAILURE;
        }

        if ($user->status == 'deleted' && $force == false) {
            $this->error('Account has already been deleted.');

            return Command::FAILURE;
        }

        if ($user->is_admin == true) {
            $this->error('Cannot delete an admin account from CLI.');

            return Command::FAILURE;
        }

        $account = AccountService::get($profile->id);

        $data = [
            'Username' => $account['username'] ?? $profile->username,
            'Statuses' => $account['statuses_count'] ?? $profile->status_count,
            'Followers' => $account['followers_count'] ?? $profile->followers_count,
            'Following' => $account['following_count'] ?? $profile->following_count,
            'Joined' => now()->parse($account['created_at'] ?? $user->created_at)->format('M Y'),
        ];

        table(
            ['Username', 'Statuses', 'Followers', 'Following', 'Joined'],
            [
                $data,
            ]
        );

        if (! $this->confirm('Are you sure you want to delete this account?')) {
            return Command::SUCCESS;
        }

        $confirmation = text('Enter the username to confirm deletion');

        if ($confirmation != $profile->username) {
            $this->error('Username does not match, exiting...');

            return Command::FAILURE;
        }

        if ($user->status !== 'deleted') {
            $profile->status = $user->status = 'deleted';
            $profile->save();
            $user->save();
        }

        DeleteAccountPipeline::dispatch($user)->onQueue('high');

        $this->info('Account deletion has been dispatched.');

        return Command::SUCCESS;
    }
}
