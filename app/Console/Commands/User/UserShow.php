<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:show {id}')]
#[Description('Show user info')]
class UserShow extends Command
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

        $this->info('User ID: '.$user->id);
        $this->info('Username: '.$user->username);
        $this->info('Email: '.$user->email);
        $this->info('Joined: '.$user->created_at->diffForHumans());
        $this->info('Status Count: '.$user->statuses()->count());
    }
}
