<?php

namespace App\Console\Commands\Admin;

use App\Models\User;
use App\Services\EmailService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('email:bancheck')]
#[Description('Checks user emails for banned domains')]
final class BannedEmailCheck extends Command
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
     */
    public function handle(): void
    {
        $users = User::whereNull('status')->get()->filter(function ($u) {
            return EmailService::isBanned($u->email) == true;
        });

        foreach ($users as $user) {
            $this->info('Found banned domain: '.$user->email.PHP_EOL);
        }
    }
}
