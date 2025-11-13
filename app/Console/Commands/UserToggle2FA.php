<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use App\User;

class UserToggle2FA extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:2fa {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable two factor authentication for given username';

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array
     */
    protected function promptForMissingArgumentsUsing()
    {
        return [
            'username' => 'Which username should we disable MFA for?',
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::whereUsername($this->argument('username'))->first();

        if(!$user) {
            $this->error('Could not find any user with that username');
            exit;
        }

        if(!$user->mfa_enabled) {
            $this->info('User did not have MFA enabled!');
            return;
        }

        $user->mfa_enabled = false;
        $user->mfa_secret = null;
        $user->mfa_backup_codes = null;
        $user->save();

        $this->info('Successfully disabled MFA on this account!');
    }
}
