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
    protected $signature = 'install {--dangerously-overwrite-env : Re-run installation and overwrite current .env }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI Installer';

    public $installType = 'Simple';

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
        $this->installType();

        if ($this->installType === 'Advanced') {
            $this->info('Installer: Advanced...');
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
            $this->validateEnv();
            $this->resetArtisanCache();
        } else {
            $this->info('Installer: Simple...');
            $this->checkDiskPermissions();
            $this->envProd();
            $this->instanceDB();
            $this->instanceRedis();
            $this->instanceURL();
            $this->activityPubSettings();
            $this->instanceSettings();
            $this->dbMigrations();
            $this->validateEnv();
            $this->resetArtisanCache();
        }
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
            exec('cp .env.example .env');
        }
    }

    protected function installType()
    {
        $type = $this->choice('Select installation type', ['Simple', 'Advanced'], 1);
        $this->installType = $type;
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
        ];

        foreach ($extensions as $ext) {
            if (extension_loaded($ext) == false) {
                $this->error("- \"{$ext}\" not found");
            } else {
                $this->info("- \"{$ext}\" found");
            }
        }

        $continue = $this->choice('Do you wish to continue?', ['yes', 'no'], 0);
        $this->continue = $continue;
        if ($this->continue === 'no') {
            $this->info('Exiting Installer.');
            exit;
        }

    }

    protected function checkFFmpegDependencies()
    {
        $this->line(' ');
        $this->info('Checking for Required FFmpeg dependencies...');

        $ffmpeg = exec('which ffmpeg');
        if (empty($ffmpeg)) {
            $this->error("- \"{$ext}\" FFmpeg not found, aborting installation");
            exit;
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
            if (is_writeable($path) == false) {
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
        $database = $this->choice('Select database driver', ['mysql', 'pgsql'], 0);
        $database_host = $this->ask('Select database host', '127.0.0.1');
        $database_port_default = $database === 'mysql' ? 3306 : 5432;
        $database_port = $this->ask('Select database port', $database_port_default);

        $database_db = $this->ask('Select database', 'pixelfed');
        $database_username = $this->ask('Select database username', 'pixelfed');
        $database_password = $this->secret('Select database password');

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
        } catch (\PDOException $e) {
            $this->error('Cannot connect to database, check your details and try again');
            exit;
        }
        $this->info('- Connected to DB Successfully');
    }

    protected function instanceRedis()
    {
        $this->line('');
        $this->info('Redis Settings:');
        $redis_client = $this->choice('Set redis client (PHP extension)', ['phpredis', 'predis'], 0);
        $redis_host = $this->ask('Set redis host', 'localhost');
        $redis_password = $this->ask('Set redis password', 'null');
        $redis_port = $this->ask('Set redis port', 6379);

        $this->updateEnvFile('REDIS_CLIENT', $redis_client);
        $this->updateEnvFile('REDIS_SCHEME', 'tcp');
        $this->updateEnvFile('REDIS_HOST', $redis_host);
        $this->updateEnvFile('REDIS_PASSWORD', $redis_password);
        $this->updateEnvFile('REDIS_PORT', $redis_port);

        $this->info('Testing Redis...');
        $redis = Redis::connection();
        if ($redis->ping()) {
            $this->info('- Connected to Redis Successfully!');
        } else {
            $this->error('Cannot connect to Redis, check your details and try again');
            exit;
        }
    }

    protected function instanceURL()
    {
        $this->line('');
        $this->info('Instance URL Settings:');
        $name = $this->ask('Site name [ex: Pixelfed]', 'Pixelfed');

        $domain = $this->ask('Site Domain [ex: pixelfed.com]');
        $domain = strtolower($domain);
        if (empty($domain)) {
            $this->error('You must set the site domain');
            exit;
        }
        if (starts_with($domain, 'http')) {
            $this->error('The site domain cannot start with https://, you must use the FQDN (eg: example.org)');
            exit;
        }
        if (strpos($domain, '.') == false) {
            $this->error('You must enter a valid site domain');
            exit;
        }

        $this->updateEnvFile('APP_NAME', $name);
        $this->updateEnvFile('APP_URL', 'https://' . $domain);
        $this->updateEnvFile('APP_DOMAIN', $domain);
        $this->updateEnvFile('ADMIN_DOMAIN', $domain);
        $this->updateEnvFile('SESSION_DOMAIN', $domain);
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
        $max_registration = $this->ask('Set Maximum users on this instance.', '1000');
        $open_registration = $this->choice('Allow new registrations?', ['false', 'true'], 0);
        $enforce_email_verification = $this->choice('Enforce email verification?', ['false', 'true'], 0);
        $enable_mobile_apis = $this->choice('Enable mobile app/apis support?', ['false', 'true'], 1);

        $this->updateEnvFile('PF_MAX_USERS', $max_registration);
        $this->updateEnvFile('OPEN_REGISTRATION', $open_registration);
        $this->updateEnvFile('ENFORCE_EMAIL_VERIFICATION', $enforce_email_verification);
        $this->updateEnvFile('OAUTH_ENABLED', $enable_mobile_apis);
        $this->updateEnvFile('EXP_EMC', $enable_mobile_apis);
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

    protected function mediaSettings()
    {
        $this->line('');
        $this->info('Media Settings:');
        $optimize_media = $this->choice('Optimize media uploads? Requires jpegoptim and other dependencies!', ['false', 'true'], 1);
        $image_quality = $this->ask('Set image optimization quality between 1-100. Default is 80%, lower values use less disk space at the expense of image quality.', '80');
        if ($image_quality < 1) {
            $this->error('Min image quality is 1. You should avoid such a low value, 60 at minimum is recommended.');
            exit;
        }
        if ($image_quality > 100) {
            $this->error('Max image quality is 100');
            exit;
        }
        $this->info('Note: Max photo size cannot exceed `post_max_size` in php.ini.');
        $max_photo_size = $this->ask('Max photo upload size in kilobytes. Default 15000 which is equal to 15MB', '15000');

        $max_caption_length = $this->ask('Max caption limit. Default to 500, max 5000.', '500');
        if ($max_caption_length > 5000) {
            $this->error('Max caption length is 5000 characters.');
            exit;
        }

        $max_album_length = $this->ask('Max photos allowed per album. Choose a value between 1 and 10.', '4');
        if ($max_album_length < 1) {
            $this->error('Min album length is 1 photos per album.');
            exit;
        }
        if ($max_album_length > 10) {
            $this->error('Max album length is 10 photos per album.');
            exit;
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
            $this->call('config:cache');
            $this->line('');
            $this->info('Migrating DB:');
            $this->call('migrate', ['--force' => true]);
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
    }

    protected function resetArtisanCache()
    {
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
    }

    protected function validateEnv()
    {
        $this->checkEnvKeys('APP_KEY', "key:generate failed?");
        $this->checkEnvKeys('APP_ENV', "APP_ENV value should be production");
        $this->checkEnvKeys('APP_DEBUG', "APP_DEBUG value should be false");
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

    protected function parseSize($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }
}
