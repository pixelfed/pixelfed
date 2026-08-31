<?php

namespace App\Console\Commands\Admin;

use App\Models\User;
use App\Services\EmailService;
use Illuminate\Console\Command;

final class BannedEmailCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:bancheck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks user emails for banned domains';

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
