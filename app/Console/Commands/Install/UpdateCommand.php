<?php

namespace App\Console\Commands\Install;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('update')]
#[Description('Run pixelfed schema updates between versions.')]
class UpdateCommand extends Command
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
        $this->update();
    }

    public function update()
    {
        $this->info('Starting update...');
        $this->line(' ');
        $this->callSilent('config:cache');
        $this->callSilent('route:cache');
        $this->callSilent('migrate', [
            '--force' => true,
        ]);
        $this->callSilent('horizon:terminate');
        $this->info('Completed update!');
    }
}
