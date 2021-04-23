<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */
 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class UserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:admin {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user an admin, or remove admin privileges.';

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
        $user = User::whereUsername($id)->orWhere('id', $id)->first();
        if(!$user) {
            $this->error('Could not find any user with that username or id.');
            exit;
        }
        $this->info('Found username: ' . $user->username);
        $state = $user->is_admin ? 'Remove admin privileges from this user?' : 'Add admin privileges to this user?';
        $confirmed = $this->confirm($state);
        if(!$confirmed) {
            exit;
        }

        $user->is_admin = !$user->is_admin;
        $user->save();
        $this->info('Successfully changed permissions!');
    }
}
