<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

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
        $ffmpeg = exec('which ffmpeg');
        if(empty($ffmpeg)) {
            $this->error('FFmpeg not found, please install it.');
            $this->error('Cancelling installation.');
            exit;
        } else {
            $this->info('- Found FFmpeg!');
        }
        $this->line('');
        $this->info('Checking for required php extensions...');
        foreach($extensions as $ext) {
            if(extension_loaded($ext) == false) {
                $this->error("- {$ext} extension not found, aborting installation");
                exit;
            } else {
            }
        }
        $this->info("- Required PHP extensions found!");
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
        if(!file_exists(base_path('.env')) || filesize(base_path('.env')) == 0) {
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
        if(!file_exists(app()->environmentFilePath())) {
            exec('cp .env.example .env');
            $this->call('key:generate');            
        }

        $name = $this->ask('Site name [ex: Pixelfed]');
        $this->updateEnvFile('APP_NAME', $name ?? 'pixelfed');

        $domain = $this->ask('Site Domain [ex: pixelfed.com]');
        $this->updateEnvFile('APP_DOMAIN', $domain ?? 'example.org');
        $this->updateEnvFile('ADMIN_DOMAIN', $domain ?? 'example.org');
        $this->updateEnvFile('SESSION_DOMAIN', $domain ?? 'example.org');
        $this->updateEnvFile('APP_URL', 'https://' . $domain ?? 'https://example.org');

        $database = $this->choice('Select database driver', ['mysql', 'pgsql'], 0);
        $this->updateEnvFile('DB_CONNECTION', $database ?? 'mysql');
        switch ($database) {
            case 'mysql':
                $database_host = $this->ask('Select database host', '127.0.0.1');
                $this->updateEnvFile('DB_HOST', $database_host ?? 'mysql');

                $database_port = $this->ask('Select database port', 3306);
                $this->updateEnvFile('DB_PORT', $database_port ?? 3306);

                $database_db = $this->ask('Select database', 'pixelfed');
                $this->updateEnvFile('DB_DATABASE', $database_db ?? 'pixelfed');

                $database_username = $this->ask('Select database username', 'pixelfed');
                $this->updateEnvFile('DB_USERNAME', $database_username ?? 'pixelfed');

                $db_pass = str_random(64);
                $database_password = $this->secret('Select database password', $db_pass);
                $this->updateEnvFile('DB_PASSWORD', $database_password);
            break;
            
        }

        $cache = $this->choice('Select cache driver', ["redis", "apc", "array", "database", "file", "memcached"], 0);
        $this->updateEnvFile('CACHE_DRIVER', $cache ?? 'redis');

        $session = $this->choice('Select session driver', ["redis", "file", "cookie", "database", "apc", "memcached", "array"], 0);
        $this->updateEnvFile('SESSION_DRIVER', $cache ?? 'redis');

        $redis_host = $this->ask('Set redis host', 'localhost');
        $this->updateEnvFile('REDIS_HOST', $redis_host);

        $redis_password = $this->ask('Set redis password', 'null');
        $this->updateEnvFile('REDIS_PASSWORD', $redis_password);

        $redis_port = $this->ask('Set redis port', 6379);
        $this->updateEnvFile('REDIS_PORT', $redis_port);

        $open_registration = $this->choice('Allow new registrations?', ['true', 'false'], 1);
        $this->updateEnvFile('OPEN_REGISTRATION', $open_registration);

        $enforce_email_verification = $this->choice('Enforce email verification?', ['true', 'false'], 0);
        $this->updateEnvFile('ENFORCE_EMAIL_VERIFICATION', $enforce_email_verification);

    }

    protected function updateEnvFile($key, $value)
    {
        $envPath = app()->environmentFilePath();
        $payload = file_get_contents($envPath);

        if ($existing = $this->existingEnv($key, $payload)) {
            $payload = str_replace("{$key}={$existing}", "{$key}=\"{$value}\"", $payload);
            $this->storeEnv($payload);
        } else {
            $payload = $payload . "\n{$key}=\"{$value}\"\n";
            $this->storeEnv($payload);
        }
    }

    protected function existingEnv($needle, $haystack)
    {
        preg_match("/^{$needle}=[^\r\n]*/m", $haystack, $matches);
        if ($matches && count($matches)) {
            return substr($matches[0], strlen($needle) + 1);
        }
        return false;
    }

    protected function storeEnv($payload)
    {
        $file = fopen(app()->environmentFilePath(), 'w');
        fwrite($file, $payload);
        fclose($file);
    }

    protected function postInstall()
    {
        $this->callSilent('config:cache');
        //$this->callSilent('route:cache');
        $this->info('Pixelfed has been successfully installed!');
    }
}
