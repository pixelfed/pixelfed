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
        if(ctype_digit($id) == true) {
            $user = User::find($id);
        } else {
            $user = User::whereUsername($id)->first();
        }
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
