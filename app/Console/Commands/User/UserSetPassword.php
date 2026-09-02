<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('user:setpassword {id : Username or numeric user id}')]
#[Description('Set/reset a user password (prompts securely, hashes with bcrypt)')]
class UserSetPassword extends Command implements PromptsForMissingInput
{
    /**
     * Prompt for missing input arguments.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'id' => 'Which username (or user id) should we set the password for?',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');

        if (ctype_digit((string) $id)) {
            $user = User::find($id);
        } else {
            $user = User::whereUsername($id)->first();
        }

        if (! $user) {
            $this->error('Could not find any user with that username or id.');

            return 1;
        }

        $this->info('Found user: '.$user->username.' (id '.$user->id.', email '.$user->email.')');

        $password = $this->secret('New password');
        if ($password === null || $password === '') {
            $this->error('Password cannot be empty.');

            return 1;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be 6 or more characters.');

            return 1;
        }

        $confirm = $this->secret('Confirm new password');
        if ($password !== $confirm) {
            $this->error('Password mismatch, aborting. No changes made.');

            return 1;
        }

        if (! $this->confirm('Set a new password for "'.$user->username.'"?', true)) {
            $this->comment('Aborted. No changes made.');

            return 0;
        }

        $user->password = bcrypt($password);
        // Invalidate "remember me" sessions so the old credential set can't linger.
        $user->remember_token = null;
        $user->save();

        $this->info('Successfully updated the password for '.$user->username.'.');
        $this->comment('Existing "remember me" tokens were cleared. Active sessions may still persist until they expire or are logged out.');

        return 0;
    }
}
