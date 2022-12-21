<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class UserUnsuspend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:unsuspend {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unsuspend a local user.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $id = $this->argument('id');
        $user = User::whereUsername($id)->orWhere('id', $id)->first();
        if(!$user) {
            $this->error('Could not find any user with that username or id.');
            exit;
        }
        $this->info('Found user, username: ' . $user->username);
        if($this->confirm('Are you sure you want to unsuspend this user?')) {
            $profile = $user->profile;
            $user->status = $profile->status = null;
            $user->save();
            $profile->save();
            $this->info('User account has been unsuspended.');
        }
    }
}
