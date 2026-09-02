<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('user:admin {username}')]
#[Description('Make a user an admin, or remove admin privileges.')]
class UserAdmin extends Command implements PromptsForMissingInput
{
    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'username' => 'Which username should we toggle admin privileges for?',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->argument('username');

        $user = User::whereUsername($id)->first();

        if (! $user) {
            $this->error('Could not find any user with that username or id.');
            exit;
        }

        $this->info('Found username: '.$user->username);
        $state = $user->is_admin ? 'Remove admin privileges from this user?' : 'Add admin privileges to this user?';
        $confirmed = $this->confirm($state);
        if (! $confirmed) {
            exit;
        }

        $user->is_admin = ! $user->is_admin;
        $user->save();
        $this->info('Successfully changed permissions!');
    }
}
