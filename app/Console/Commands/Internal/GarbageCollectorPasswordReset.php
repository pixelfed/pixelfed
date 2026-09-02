<?php

namespace App\Console\Commands\Internal;

use App\Models\EmailVerification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gc:passwordreset')]
#[Description('Delete password reset tokens over 24 hours old')]
class GarbageCollectorPasswordReset extends Command
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
        EmailVerification::where('created_at', '<', now()->subMinutes(1441))
            ->chunk(50, function ($emails) {
                foreach ($emails as $em) {
                    $em->delete();
                }
            });
    }
}
