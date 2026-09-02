<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:suspend {id}')]
#[Description('Suspend a local user.')]
class UserSuspend extends Command
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
        $id = $this->argument('id');
        if (ctype_digit($id) == true) {
            $user = User::find($id);
        } else {
            $user = User::whereUsername($id)->first();
        }
        if (! $user) {
            $this->error('Could not find any user with that username or id.');
            exit;
        }
        $this->info('Found user, username: '.$user->username);
        if ($this->confirm('Are you sure you want to suspend this user?')) {
            $profile = $user->profile;
            $user->status = $profile->status = 'suspended';
            $user->save();
            $profile->save();
            $this->info('User account has been suspended.');
        }
    }
}
