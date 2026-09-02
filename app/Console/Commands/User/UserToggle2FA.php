<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('user:2fa {username}')]
#[Description('Disable two factor authentication for given username')]
class UserToggle2FA extends Command implements PromptsForMissingInput
{
    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'username' => 'Which username should we disable 2FA for?',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::whereUsername($this->argument('username'))->first();

        if (! $user) {
            $this->error('Could not find any user with that username');
            exit;
        }

        if (! $user->{'2fa_enabled'}) {
            $this->info('User did not have 2FA enabled!');

            return;
        }

        $user->{'2fa_enabled'} = false;
        $user->{'2fa_secret'} = null;
        $user->{'2fa_backup_codes'} = null;
        $user->save();

        $this->info('Successfully disabled 2FA on this account!');
    }
}
