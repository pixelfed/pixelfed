<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\EmailVerification;

class GCPasswordReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gc:passwordreset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete password reset tokens over 24 hours old';

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
            ->chunk(50, function($emails) {
                foreach($emails as $em) {
                    $em->delete();
                }
            });
    }
}
