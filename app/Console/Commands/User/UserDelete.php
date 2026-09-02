<?php

namespace App\Console\Commands\User;

use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Models\Profile;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

#[Signature('user:delete {id} {--force}')]
#[Description('Delete account')]
class UserDelete extends Command implements PromptsForMissingInput
{
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

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

        $user = Profile::where('username', 'like', '%'.$id.'%')->orWhere('user_id', $id)->orWhere('id', $id)->orderByDesc('followers_count')->get();
        if (! $user || ! $user->count()) {
            $this->error('Invalid user id or username');

            return;
        }
        $user = select(
            'Select the account',
            $user->map(function ($u) {
                return $u->username;
            })
        );
        $user = Profile::whereUsername($user)->first();

        if (! $user) {
            $this->error('Invalid id or username');

            return;
        }

        $this->info($user->username);

        return;

        if (ctype_digit($id) == true) {
            $user = User::find($id);
        } else {
            $user = User::whereUsername($id)->first();
        }

        if (! $user) {
            $this->error('Could not find any user with that username or id.');
            exit;
        }

        if ($user->status == 'deleted' && $force == false) {
            $this->error('Account has already been deleted.');

            return;
        }

        if ($user->is_admin == true) {
            $this->error('Cannot delete an admin account from CLI.');
            exit;
        }

        $account = AccountService::get($user->profile_id);

        $data = [
            'Username' => $account['username'],
            'Statuses' => $account['statuses_count'],
            'Followers' => $account['followers_count'],
            'Following' => $account['following_count'],
            'Joined' => now()->parse($account['created_at'])->format('M Y'),
        ];

        table(
            ['Username', 'Statuses', 'Followers', 'Following', 'Joined'],
            [
                $data,
            ]
        );

        if (! $this->confirm('Are you sure you want to delete this account?')) {
            exit;
        }

        $confirmation = text('Enter the username to confirm deletion');

        if ($confirmation != $user->username) {
            $this->error('Username does not match, exiting...');
            exit;
        }

        return;
        if ($user->status !== 'deleted') {
            $profile = $user->profile;
            $profile->status = $user->status = 'deleted';
            $profile->save();
            $user->save();
        }

        DeleteAccountPipeline::dispatch($user)->onQueue('high');
    }
}
