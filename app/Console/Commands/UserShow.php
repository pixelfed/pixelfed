<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class UserShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:show {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show user info';

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

        $this->info('User ID: ' . $user->id);
        $this->info('Username: ' . $user->username);
        $this->info('Email: ' . $user->email);
        $this->info('Joined: ' . $user->created_at->diffForHumans());
        $this->info('Status Count: ' . $user->statuses()->count());
    }
}
