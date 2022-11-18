<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\User;

class UserVerifyEmail extends Command
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
        $user = User::whereUsername($this->argument('username'))->first();

        if(!$user) {
            $this->error('Username not found');
            return;
        }

        $user->email_verified_at = now();
        $user->save();
        $this->info('Successfully verified email address for ' . $user->username);
    }
}
