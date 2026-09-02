<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('user:verifyemail {username}')]
#[Description('Verify user email address')]
class UserVerifyEmail extends Command implements PromptsForMissingInput
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $username = $this->argument('username');
        $user = User::whereUsername($username)->first();

        if (! $user) {
            $this->error('Username not found');

            return;
        }

        if ($user->email_verified_at) {
            $this->error('Email already verified '.$user->email_verified_at->diffForHumans());

            return;
        }

        $user->email_verified_at = now();
        $user->save();
        $this->info('Successfully verified email address for '.$user->username);
    }
}
