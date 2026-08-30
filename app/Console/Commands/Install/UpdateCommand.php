<?php

namespace App\Console\Commands\Install;

use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run pixelfed schema updates between versions.';

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
