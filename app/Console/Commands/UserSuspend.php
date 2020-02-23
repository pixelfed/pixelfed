<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class UserSuspend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:suspend {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suspend a local user.';

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
     * @return void
     */
    public function handle(): void
    {
        $id = $this->argument('id');
        $user = User::whereUsername($id)->orWhere('id', $id)->first();
        if(!$user) {
            $this->error('Could not find any user with that username or id.');
            exit;
        }
        $this->info('Found user, username: ' . $user->username);
        if($this->confirm('Are you sure you want to suspend this user?')) {
            $profile = $user->profile;
            $user->status = $profile->status = 'suspended';
            $user->save();
            $profile->save();
            $this->info('User account has been suspended.');
        }
    }
}
