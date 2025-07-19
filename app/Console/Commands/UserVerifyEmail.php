<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class UserVerifyEmail extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:verifyemail {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify user email address';

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
