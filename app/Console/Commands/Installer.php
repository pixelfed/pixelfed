<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use \PDO;

class Installer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install 
        {--dangerously-overwrite-env : Re-run installation and overwrite current .env}
        {--domain= : Pre-fill site domain}
        {--name= : Pre-fill site name}
        {--email= : Pre-fill admin email}
        {--db-driver= : Pre-fill database driver (mysql/pgsql)}
        {--db-host= : Pre-fill database host}
        {--db-port= : Pre-fill database port}
        {--db-database= : Pre-fill database name}
        {--db-username= : Pre-fill database username}
        {--db-password= : Pre-fill database password}
        {--redis-host= : Pre-fill Redis host}
        {--redis-port= : Pre-fill Redis port}
        {--redis-password= : Pre-fill Redis password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI Installer';

    protected $migrationsRan = false;

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
        $this->installerSteps();
    }

    protected function installerSteps()
    {
        $this->envCheck();
        $this->envCreate();
        $this->checkPHPRequiredDependencies();
        $this->checkFFmpegDependencies();
        $this->checkOptimiseDependencies();
        $this->checkDiskPermissions();
        $this->envProd();
        $this->instanceDB();
        $this->instanceRedis();
        $this->instanceURL();
        $this->activityPubSettings();
        $this->laravelSettings();
        $this->instanceSettings();
        $this->mediaSettings();
        $this->dbMigrations();
        $this->setupPrep();
        $this->validateEnv();
        $this->resetArtisanCache();
    }

    protected function envCheck()
    {
        if (file_exists(base_path('.env')) &&
            filesize(base_path('.env')) !== 0 &&
            !$this->option('dangerously-overwrite-env')
        ) {
            $this->line('');
            $this->error('Existing .env File Found - Installation Aborted');
            $this->line('Run the following command to re-run the installer: php artisan install --dangerously-overwrite-env');
            $this->line('');
            exit;
        }
    }

    protected function envCreate()
    {
        $this->line('');
        $this->info('Creating .env if required');
        if (!file_exists(app()->environmentFilePath())) {
            copy(base_path('.env.example'), app()->environmentFilePath());
        }
    }

    protected function checkPHPRequiredDependencies()
    {
        $this->line(' ');
        $this->info('Checking for Required PHP Extensions...');

        $extensions = [
            'bcmath',
            'ctype',
            'curl',
            'json',
            'mbstring',
            'openssl',
            'gd',
            'intl',
            'xml',
            'zip',
            'redis',
            'vips',
        ];

        $missing = [];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext) == false) {
                $this->error("- \"{$ext}\" not found");
                $missing[] = $ext;
            } else {
                $this->info("- \"{$ext}\" found");
            }
        }

        if (!empty($missing)) {
            $continue = $this->choice('Some extensions are missing. Do you wish to continue?', ['yes', 'no'], 1);
            if ($continue === 'no') {
                $this->info('Exiting Installer.');
                return 1;
            }
        }

    }

    protected function checkFFmpegDependencies()
    {
        $this->line(' ');
        $this->info('Checking for FFmpeg (required for video processing)...');

        $ffmpeg = exec('which ffmpeg');
        if (empty($ffmpeg)) {
            $this->warn('- FFmpeg not found');
            $this->warn('  Video uploads will not work without FFmpeg');
            $continue = $this->choice('Do you want to continue without FFmpeg?', ['yes', 'no'], 1);
            if ($continue === 'no') {
                $this->info('Exiting Installer. Please install FFmpeg and try again.');
                return 1;
            }
        } else {
            $this->info('- Found FFmpeg!');
        }
    }

    protected function checkOptimiseDependencies()
    {
        $this->line(' ');
        $this->info('Checking for Optional Media Optimisation dependencies...');

        $dependencies = [
            'jpegoptim',
            'optipng',
            'pngquant',
            'gifsicle',
        ];

        foreach ($dependencies as $dep) {
            $which = exec("which $dep");
            if (empty($which)) {
                $this->error("- \"{$dep}\" not found");
            } else {
                $this->info("- \"{$dep}\" found");
            }
        }
    }

    protected function checkDiskPermissions()
    {
        $this->line('');
        $this->info('Checking for proper filesystem permissions...');
        $this->callSilently('storage:link');

        $paths = [
            base_path('bootstrap'),
            base_path('storage'),
        ];

        foreach ($paths as $path) {
            if (is_writable($path) == false) {
                $this->error("- Invalid permission found! Aborting installation.");
                $this->error("  Please make the following path writeable by the web server:");
                $this->error("  $path");
                exit;
            } else {
                $this->info("- Found valid permissions for {$path}");
            }
        }
    }

    protected function envProd()
    {
        $this->line('');
        $this->info('Enabling production');

        $this->updateEnvFile('APP_ENV', 'production');
        $this->updateEnvFile('APP_DEBUG', 'false');
        $this->call('key:generate', ['--force' => true]);
    }

    protected function instanceDB()
    {
        $this->line('');
        $this->info('Database Settings:');
        
        $database = $this->choice('Select database driver', ['mysql', 'pgsql'], $this->option('db-driver') ?: 0);
        $database_host = $this->ask('Select database host', $this->option('db-host') ?: '127.0.0.1');
        $database_port_default = $database === 'mysql' ? 3306 : 5432;
        $database_port = $this->ask('Select database port', $this->option('db-port') ?: $database_port_default);

        $database_db = $this->ask('Select database', $this->option('db-database') ?: 'pixelfed');
        $database_username = $this->ask('Select database username', $this->option('db-username') ?: 'pixelfed');
        $database_password = $this->ask('Select database password', $this->option('db-password') ?: null);

        $this->updateEnvFile('DB_CONNECTION', $database);
        $this->updateEnvFile('DB_HOST', $database_host);
        $this->updateEnvFile('DB_PORT', $database_port);
        $this->updateEnvFile('DB_DATABASE', $database_db);
        $this->updateEnvFile('DB_USERNAME', $database_username);
        $this->updateEnvFile('DB_PASSWORD', $database_password);

        $this->info('Testing Database...');
        $dsn = "{$database}:dbname={$database_db};host={$database_host};port={$database_port};";
        try {
            $dbh = new PDO($dsn, $database_username, $database_password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $dbh = null; // Close connection
        } catch (\PDOException $e) {
            $this->error('Cannot connect to database, check your details and try again');
            $this->error('Error: ' . $e->getMessage());
            exit;
        }
        $this->info('- Connected to DB Successfully');
    }

    protected function instanceRedis()
    {
        $this->line('');
        $this->info('Redis Settings:');
        $redis_client = $this->choice('Set redis client (PHP extension)', ['phpredis', 'predis'], 0);
        $redis_host = $this->ask('Set redis host', $this->option('redis-host') ?: 'localhost');
        $redis_password = $this->ask('Set redis password (leave empty for none)', $this->option('redis-password') ?? '');
        $redis_port = $this->ask('Set redis port', $this->option('redis-port') ?: 6379);

        $this->updateEnvFile('REDIS_CLIENT', $redis_client);
        $this->updateEnvFile('REDIS_SCHEME', 'tcp');
        $this->updateEnvFile('REDIS_HOST', $redis_host);
        $this->updateEnvFile('REDIS_PASSWORD', empty($redis_password) ? 'null' : $redis_password);
        $this->updateEnvFile('REDIS_PORT', $redis_port);

        $this->info('Testing Redis...');
        $this->call('config:clear');
        try {
            $redis = Redis::connection();
            if ($redis->ping()) {
                $this->info('- Connected to Redis Successfully!');
            } else {
                $this->error('Cannot connect to Redis, check your details and try again');
                exit;
            }
        } catch (\Exception $e) {
            $this->error('Cannot connect to Redis, check your details and try again');
            $this->error('Error: ' . $e->getMessage());
            exit;
        }
    }

    protected function instanceURL()
    {
        $this->line('');
        $this->info('Instance URL Settings:');
        $name = $this->ask('Site name [ex: Pixelfed]', $this->option('name') ?: 'Pixelfed');

        $domain = '';
        while (empty($domain)) {
            $domain = $this->ask('Site Domain [ex: pixelfed.com]', $this->option('domain') ?: null);
            $domain = strtolower(trim($domain));
            
            if (empty($domain)) {
                $this->error('You must set the site domain');
                continue;
            }
            
            if (str_starts_with($domain, 'http://') || str_starts_with($domain, 'https://')) {
                $this->error('The site domain cannot start with http:// or https://, you must use the FQDN (eg: example.org)');
                $domain = '';
                continue;
            }
            
            // Better domain validation
            if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $domain)) {
                $this->error('Invalid domain format. Please enter a valid domain (eg: example.org)');
                $domain = '';
                continue;
            }
        }

        $this->updateEnvFile('APP_NAME', $name);
        $this->updateEnvFile('APP_URL', 'https://' . $domain);
        $this->updateEnvFile('APP_DOMAIN', $domain);
        $this->updateEnvFile('ADMIN_DOMAIN', $domain);
        $this->updateEnvFile('SESSION_DOMAIN', $domain);
    }

    protected function activityPubSettings()
    {
        $this->line('');
        $this->info('Federation Settings:');
        $activitypub_federation = $this->choice('Enable ActivityPub federation?', ['false', 'true'], 1);

        $this->updateEnvFile('ACTIVITY_PUB', $activitypub_federation);
        $this->updateEnvFile('AP_REMOTE_FOLLOW', $activitypub_federation);
        $this->updateEnvFile('AP_INBOX', $activitypub_federation);
        $this->updateEnvFile('AP_OUTBOX', $activitypub_federation);
        $this->updateEnvFile('AP_SHAREDINBOX', $activitypub_federation);
    }

    protected function laravelSettings()
    {
        $this->line('');
        $this->info('Laravel Settings (Defaults are recommended):');
        $session = $this->choice('Select session driver', ["database", "file", "cookie", "redis", "memcached", "array"], 0);
        $cache = $this->choice('Select cache driver', ["redis", "apc", "array", "database", "file", "memcached"], 0);
        $queue = $this->choice('Select queue driver', ["redis", "database", "sync", "beanstalkd", "sqs", "null"], 0);
        $broadcast = $this->choice('Select broadcast driver', ["log", "redis", "pusher", "null"], 0);
        $log = $this->choice('Select Log Channel', ["stack", "single", "daily", "stderr", "syslog", "null"], 0);
        $horizon = $this->ask('Set Horizon Prefix [ex: horizon-]', 'horizon-');

        $this->updateEnvFile('SESSION_DRIVER', $session);
        $this->updateEnvFile('CACHE_DRIVER', $cache);
        $this->updateEnvFile('QUEUE_DRIVER', $queue);
        $this->updateEnvFile('BROADCAST_DRIVER', $broadcast);
        $this->updateEnvFile('LOG_CHANNEL', $log);
        $this->updateEnvFile('HORIZON_PREFIX', $horizon);
    }

    protected function instanceSettings()
    {
        $this->line('');
        $this->info('Instance Settings:');
        
        $max_registration = '';
        while (!is_numeric($max_registration) || $max_registration < 1) {
            $max_registration = $this->ask('Set Maximum users on this instance', '1000');
            if (!is_numeric($max_registration) || $max_registration < 1) {
                $this->error('Please enter a valid number greater than 0');
            }
        }
        
        $open_registration = $this->choice('Allow new registrations?', ['false', 'true'], 0);
        $enforce_email_verification = $this->choice('Enforce email verification?', ['false', 'true'], 0);
        $enable_mobile_apis = $this->choice('Enable mobile app/apis support?', ['false', 'true'], 1);

        $this->updateEnvFile('PF_MAX_USERS', $max_registration);
        $this->updateEnvFile('OPEN_REGISTRATION', $open_registration);
        $this->updateEnvFile('ENFORCE_EMAIL_VERIFICATION', $enforce_email_verification);
        $this->updateEnvFile('OAUTH_ENABLED', $enable_mobile_apis);
        $this->updateEnvFile('EXP_EMC', $enable_mobile_apis);
    }

    protected function mediaSettings()
    {
        $this->line('');
        $this->info('Media Settings:');
        $optimize_media = $this->choice('Optimize media uploads? Requires jpegoptim and other dependencies!', ['false', 'true'], 1);
        
        $image_quality = '';
        while (!is_numeric($image_quality) || $image_quality < 1 || $image_quality > 100) {
            $image_quality = $this->ask('Set image optimization quality between 1-100 (default: 80)', '80');
            if (!is_numeric($image_quality) || $image_quality < 1 || $image_quality > 100) {
                $this->error('Please enter a number between 1 and 100');
            }
        }
        
        $this->info('Note: Max photo size cannot exceed `post_max_size` in php.ini.');
        $max_photo_size = '';
        while (!is_numeric($max_photo_size) || $max_photo_size < 1) {
            $max_photo_size = $this->ask('Max photo upload size in kilobytes (default: 15000 = 15MB)', '15000');
            if (!is_numeric($max_photo_size) || $max_photo_size < 1) {
                $this->error('Please enter a valid number greater than 0');
            }
        }

        $max_caption_length = '';
        while (!is_numeric($max_caption_length) || $max_caption_length < 1 || $max_caption_length > 5000) {
            $max_caption_length = $this->ask('Max caption limit (1-5000, default: 500)', '500');
            if (!is_numeric($max_caption_length) || $max_caption_length < 1 || $max_caption_length > 5000) {
                $this->error('Please enter a number between 1 and 5000');
            }
        }

        $max_album_length = '';
        while (!is_numeric($max_album_length) || $max_album_length < 1 || $max_album_length > 10) {
            $max_album_length = $this->ask('Max photos per album (1-10, default: 4)', '4');
            if (!is_numeric($max_album_length) || $max_album_length < 1 || $max_album_length > 10) {
                $this->error('Please enter a number between 1 and 10');
            }
        }

        $this->updateEnvFile('PF_OPTIMIZE_IMAGES', $optimize_media);
        $this->updateEnvFile('IMAGE_QUALITY', $image_quality);
        $this->updateEnvFile('MAX_PHOTO_SIZE', $max_photo_size);
        $this->updateEnvFile('MAX_CAPTION_LENGTH', $max_caption_length);
        $this->updateEnvFile('MAX_ALBUM_LENGTH', $max_album_length);
    }

    protected function dbMigrations()
    {
        $this->line('');
        $this->info('Note: We recommend running database migrations now!');
        $confirm = $this->choice('Do you want to run the database migrations?', ['Yes', 'No'], 0);

        if ($confirm === 'Yes') {
            sleep(3);
            
            // Clear any cached config
            $this->call('config:clear');
            
            // Force reload environment variables
            $app = app();
            $app->bootstrapWith([
                \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
            ]);
            
            // Purge database connections to force reconnect with new credentials
            $app->forgetInstance('db');
            $app->forgetInstance('db.connection');
            \Illuminate\Support\Facades\DB::purge();
            
            // Rebuild config cache
            $this->call('config:cache');
            
            $this->line('');
            $this->info('Migrating DB:');
            $this->call('migrate', ['--force' => true]);
            $this->migrationsRan = true;
        }
    }

    protected function setupPrep()
    {
        if (!$this->migrationsRan) {
            $this->warn('Skipping setup tasks because migrations were not run.');
            $this->warn('You can run these commands manually later:');
            $this->warn('  php artisan import:cities');
            $this->warn('  php artisan instance:actor');
            $this->warn('  php artisan passport:keys');
            return;
        }

        $this->line('');
        $this->info('Running setup tasks...');
        $this->line('');
        $this->info('Importing Cities:');
        $this->call('import:cities');
        $this->line('');
        $this->info('Creating Federation Instance Actor:');
        $this->call('instance:actor');
        $this->line('');
        $this->info('Creating Password Keys for API:');
        $this->call('passport:keys', ['--force' => true]);

        $confirm = $this->choice('Do you want to create an admin account?', ['Yes', 'No'], 0);
        if ($confirm === 'Yes') {
            $this->call('user:create');
        }
    }

    protected function validateEnv()
    {
        $this->checkEnvKeys('APP_KEY', "key:generate failed?");
        $this->checkEnvKeys('APP_ENV', "APP_ENV value should be production");
        $this->checkEnvKeys('APP_DEBUG', "APP_DEBUG value should be false");
    }

    protected function resetArtisanCache()
    {
        $this->call('config:clear');
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
        $this->line('');
    }

#####
    # Installer Functions
    #####

    protected function checkEnvKeys($key, $error)
    {
        $envPath = app()->environmentFilePath();
        $payload = file_get_contents($envPath);

        if ($existing = $this->existingEnv($key, $payload)) {
        } else {
            $this->error("$key empty - $error");
        }
    }

    protected function updateEnvFile($key, $value)
    {
        $envPath = app()->environmentFilePath();
        $payload = file_get_contents($envPath);
        
        // Escape special characters for .env format
        $value = str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $value);

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
        $envPath = app()->environmentFilePath();
        $tempPath = $envPath . '.tmp';
        
        // Write to temp file first
        $file = fopen($tempPath, 'w');
        if ($file === false) {
            throw new \RuntimeException("Cannot write to {$tempPath}");
        }
        
        fwrite($file, $payload);
        fclose($file);
        
        // Atomic rename
        if (!rename($tempPath, $envPath)) {
            @unlink($tempPath);
            throw new \RuntimeException("Cannot update .env file");
        }
    }
}
