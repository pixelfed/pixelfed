<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Jobs\DeletePipeline\DeleteAccountPipeline;

class UserDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete {id} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete account';

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
        $force = $this->option('force');

        if(ctype_digit($id) == true) {
            $user = User::find($id);
        } else {
            $user = User::whereUsername($id)->first();
        }

        if(!$user) {
            $this->error('Could not find any user with that username or id.');
            exit;
        }

        if($user->status == 'deleted' && $force == false) {
            $this->error('Account has already been deleted.');
            return;
        }

        if($user->is_admin == true) {
            $this->error('Cannot delete an admin account from CLI.');
            exit;
        }

        if(!$this->confirm('Are you sure you want to delete this account?')) {
            exit;
        }

        $confirmation = $this->ask('Enter the username to confirm deletion');

        if($confirmation !== $user->username) {
            $this->error('Username does not match, exiting...');
            exit;
        }

        if($user->status !== 'deleted') {
            $profile = $user->profile;
            $profile->status = $user->status = 'deleted';
            $profile->save();
            $user->save();
        }

        DeleteAccountPipeline::dispatch($user)->onQueue('high');
    }
}
