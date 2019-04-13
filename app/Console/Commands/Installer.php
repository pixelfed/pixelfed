<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Redis;

class Installer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI Installer';

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
        $this->welcome();
    }

    protected function welcome()
    {
        $this->info('       ____  _           ______         __  ');
        $this->info('      / __ \(_)  _____  / / __/__  ____/ /  ');
        $this->info('     / /_/ / / |/_/ _ \/ / /_/ _ \/ __  /   ');
        $this->info('    / ____/ />  </  __/ / __/  __/ /_/ /    ');
        $this->info('   /_/   /_/_/|_|\___/_/_/  \___/\__,_/     ');
        $this->info(' ');
        $this->info('    Welcome to the Pixelfed Installer!');
        $this->info(' ');
        $this->info(' ');
        $this->info('Pixelfed version: ' . config('pixelfed.version'));
        $this->line(' ');
        $this->info('Scanning system...');                               
        $this->preflightCheck();
    }
    protected function preflightCheck()
    {
        $this->line(' ');
        $this->info('Checking for installed dependencies...');
        $redis = Redis::connection();
        if($redis->ping()) {
            $this->info('- Found redis!');
        } else {
            $this->error('- Redis not found, aborting installation');
            exit;
        }
        $this->checkPhpDependencies();
        $this->checkPermissions();
        $this->envCheck();
    }

    protected function checkPhpDependencies()
    {
        $extensions = [
            'bcmath',
            'ctype',
            'curl',
            'json',
            'mbstring',
            'openssl'
        ];
        $this->line('');
        $this->info('Checking for required php extensions...');
        foreach($extensions as $ext) {
            if(extension_loaded($ext) == false) {
                $this->error("- {$ext} extension not found, aborting installation");
                exit;
            } else {
                $this->info("- {$ext} extension found!");
            }
        }
    }

    protected function checkPermissions()
    {
        $this->line('');
        $this->info('Checking for proper filesystem permissions...');

        $paths = [
            base_path('bootstrap'),
            base_path('storage')
        ];

        foreach($paths as $path) {
            if(is_writeable($path) == false) {
                $this->error("- Invalid permission found! Aborting installation.");
                $this->error("  Please make the following path writeable by the web server:");
                $this->error("  $path");
                exit;
            } else {
                $this->info("- Found valid permissions for {$path}");
            }
        }
    }

    protected function envCheck()
    {
        if(!file_exists(base_path('.env'))) {
            $this->line('');
            $this->info('No .env configuration file found. We will create one now!');
            $this->createEnv();
        } else {
            $confirm = $this->confirm('Found .env file, do you want to overwrite it?');
            if(!$confirm) {
                $this->info('Cancelling installation.');
                exit;
            }
            $confirm = $this->confirm('Are you really sure you want to overwrite it?');
            if(!$confirm) {
                $this->info('Cancelling installation.');
                exit;
            }
            $this->error('Warning ... if you did not backup your .env before its overwritten it will be permanently deleted.');
            $confirm = $this->confirm('The application may be installed already, are you really sure you want to overwrite it?');
            if(!$confirm) {
                $this->info('Cancelling installation.');
                exit;
            }
        }
        $this->postInstall();
    }

    protected function createEnv()
    {
        $this->line('');
        // copy env
        $name = $this->ask('Site name [ex: Pixelfed]');
        $domain = $this->ask('Site Domain [ex: pixelfed.com]');
        $tls = $this->choice('Use HTTPS/TLS?', ['https', 'http'], 0);
        $dbDrive = $this->choice('Select database driver', ['mysql', 'pgsql'/*, 'sqlite', 'sqlsrv'*/], 0);
        $ws = $this->choice('Select cache driver', ["apc", "array", "database", "file", "memcached", "redis"], 5);

    }

    protected function postInstall()
    {
        $this->callSilent('config:cache');
        //$this->call('route:cache');
        $this->info('Pixelfed has been successfully installed!');
    }
}
