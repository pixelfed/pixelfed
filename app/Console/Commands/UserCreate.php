<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class UserCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {--name=} {--username=} {--email=} {--password} {--is_admin=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

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
        $this->info('Creating a new user...');
        @empty ($name)
            $name = $this->ask('Name');
        @endempty
        @empty ($username)
            $username = $this->ask('Username');
        @endempty
        if(User::whereUsername($username)->exists()) {
            $this->error('Username already in use, please try again...');
            exit;
        }
        @empty ($email)
            $email = $this->ask('Email');
            $confirm_email = $this->confirm('Manually verify email address?');
        @endempty
        if(User::whereEmail($email)->exists()) {
            $this->error('Email already in use, please try again...');
            exit;
        }
        @empty ($password)
            $password = $this->secret('Password');
            $confirm = $this->secret('Confirm Password');
            if($password !== $confirm) {
                $this->error('Password mismatch, please try again...');
                exit;
            }
        @endempty
        @empty ($is_admin)
        $is_admin = $this->confirm('Make this user an admin?');
        @endempty
        {
            $user = new User;
            $user->username = $username;
            $user->name = $name;
            $user->email = $email;
            $user->password = bcrypt($password);
            $user->is_admin = $is_admin;
            $user->email_verified_at = $confirm_email ? now() : null;
            $user->save();

            $this->info('Created new user!');
        }
    }
}