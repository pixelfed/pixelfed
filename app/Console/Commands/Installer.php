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
        $this->envCheck();
    }

    protected function envCheck()
    {
        if( file_exists(base_path('.env')) &&
        	filesize(base_path('.env')) !== 0 &&
        	!$this->option('dangerously-overwrite-env')
        ) {
            $this->line('');
            $this->error('Installation aborted, found existing .env file');
            $this->line('Run the following command to re-run the installer:');
            $this->line('');
            $this->info('php artisan install --dangerously-overwrite-env');
            $this->line('');
            exit;
        }
        $this->installType();
    }

    protected function installType()
    {
    	$type = $this->choice('Select installation type', ['Simple', 'Advanced'], 0);
		$this->installType = $type;
        $this->preflightCheck();
    }

    protected function preflightCheck()
    {
        if($this->installType === 'Advanced') {
			$this->info('Scanning system...');
			$this->line(' ');
			$this->info('Checking for installed dependencies...');
	        $redis = Redis::connection();
	        if($redis->ping()) {
	            $this->info('- Found redis!');
	        } else {
	            $this->error('- Redis not found, aborting installation');
	            exit;
	        }
        }
        $this->checkPhpDependencies();
    }

    protected function checkPhpDependencies()
    {
        $extensions = [
            'bcmath',
            'ctype',
            'curl',
            'json',
            'mbstring',
            'openssl',
        ];
        if($this->installType === 'Advanced') {
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
	    }
        foreach($extensions as $ext) {
            if(extension_loaded($ext) == false) {
                $this->error("\"{$ext}\" PHP extension not found, aborting installation");
                exit;
            }
        }
        if($this->installType === 'Advanced') {
	        $this->info("- Required PHP extensions found!");
	    }

	    $this->checkPermissions();
    }

    protected function checkPermissions()
    {
    	if($this->installType === 'Advanced') {
	        $this->line('');
	        $this->info('Checking for proper filesystem permissions...');
	    }

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
            	if($this->installType === 'Advanced') {
	                $this->info("- Found valid permissions for {$path}");
	            }
            }
        }

        $this->createEnv();
    }

    protected function createEnv()
    {
        $this->line('');
        if(!file_exists(app()->environmentFilePath())) {
            exec('cp .env.example .env');
            $this->updateEnvFile('APP_ENV', 'setup');
            $this->call('key:generate');
        }

        $name = $this->ask('Site name [ex: Pixelfed]');
        $this->updateEnvFile('APP_NAME', $name ?? 'pixelfed');

        $domain = $this->ask('Site Domain [ex: pixelfed.com]');
        if(empty($domain)) {
        	$this->error('You must set the site domain');
        	exit;
        }
        if(starts_with($domain, 'http')) {
        	$this->error('The site domain cannot start with https://, you must use the FQDN (eg: example.org)');
        	exit;
        }
        if(strpos($domain, '.') == false) {
        	$this->error('You must enter a valid site domain');
        	exit;
        }
        $this->updateEnvFile('APP_DOMAIN', $domain ?? 'example.org');
        $this->updateEnvFile('ADMIN_DOMAIN', $domain ?? 'example.org');
        $this->updateEnvFile('SESSION_DOMAIN', $domain ?? 'example.org');
        $this->updateEnvFile('APP_URL', 'https://' . $domain);

        $database = $this->choice('Select database driver', ['mysql', 'pgsql'], 0);
        $this->updateEnvFile('DB_CONNECTION', $database ?? 'mysql');

        $database_host = $this->ask('Select database host', '127.0.0.1');
        $this->updateEnvFile('DB_HOST', $database_host ?? 'mysql');

        $database_port_default = $database === 'mysql' ? 3306 : 5432;
        $database_port = $this->ask('Select database port', $database_port_default);
        $this->updateEnvFile('DB_PORT', $database_port ?? $database_port_default);

        $database_db = $this->ask('Select database', 'pixelfed');
        $this->updateEnvFile('DB_DATABASE', $database_db ?? 'pixelfed');

        $database_username = $this->ask('Select database username', 'pixelfed');
        $this->updateEnvFile('DB_USERNAME', $database_username ?? 'pixelfed');

        $db_pass = str_random(64);
        $database_password = $this->secret('Select database password', $db_pass);
        $this->updateEnvFile('DB_PASSWORD', $database_password);

        $dsn = "{$database}:dbname={$database_db};host={$database_host};port={$database_port};";
        try {
        	$dbh = new PDO($dsn, $database_username, $database_password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (\PDOException $e) {
        	$this->error('Cannot connect to database, check your credentials and try again');
        	exit;
        }

        if($this->installType === 'Advanced') {
	        $cache = $this->choice('Select cache driver', ["redis", "apc", "array", "database", "file", "memcached"], 0);
	        $this->updateEnvFile('CACHE_DRIVER', $cache ?? 'redis');

	        $session = $this->choice('Select session driver', ["redis", "file", "cookie", "database", "apc", "memcached", "array"], 0);
	        $this->updateEnvFile('SESSION_DRIVER', $session ?? 'redis');

	        $redis_host = $this->ask('Set redis host', 'localhost');
	        $this->updateEnvFile('REDIS_HOST', $redis_host);

	        $redis_password = $this->ask('Set redis password', 'null');
	        $this->updateEnvFile('REDIS_PASSWORD', $redis_password);

	        $redis_port = $this->ask('Set redis port', 6379);
	        $this->updateEnvFile('REDIS_PORT', $redis_port);
	    }

        $open_registration = $this->choice('Allow new registrations?', ['false', 'true'], 0);
        $this->updateEnvFile('OPEN_REGISTRATION', $open_registration);

        $activitypub_federation = $this->choice('Enable ActivityPub federation?', ['false', 'true'], 1);
        $this->updateEnvFile('ACTIVITY_PUB', $activitypub_federation);
        $this->updateEnvFile('AP_INBOX', $activitypub_federation);
        $this->updateEnvFile('AP_SHAREDINBOX', $activitypub_federation);
        $this->updateEnvFile('AP_REMOTE_FOLLOW', $activitypub_federation);

        $enforce_email_verification = $this->choice('Enforce email verification?', ['false', 'true'], 1);
        $this->updateEnvFile('ENFORCE_EMAIL_VERIFICATION', $enforce_email_verification);

        $enable_mobile_apis = $this->choice('Enable mobile app/apis support?', ['false', 'true'], 1);
        $this->updateEnvFile('OAUTH_ENABLED', $enable_mobile_apis);
        $this->updateEnvFile('EXP_EMC', $enable_mobile_apis);

    	$optimize_media = $this->choice('Optimize media uploads? Requires jpegoptim and other dependencies!', ['false', 'true'], 0);
    	$this->updateEnvFile('PF_OPTIMIZE_IMAGES', $optimize_media);

        if($this->installType === 'Advanced') {

        	if($optimize_media === 'true') {
	        	$image_quality = $this->ask('Set image optimization quality between 1-100. Default is 80%, lower values use less disk space at the expense of image quality.', '80');
	        	if($image_quality < 1) {
	        		$this->error('Min image quality is 1. You should avoid such a low value, 60 at minimum is recommended.');
	        		exit;
	        	}
	        	if($image_quality > 100) {
	        		$this->error('Max image quality is 100');
	        		exit;
	        	}
	    		$this->updateEnvFile('IMAGE_QUALITY', $image_quality);
        	}

        	$max_photo_size = $this->ask('Max photo upload size in kilobytes. Default 15000 which is equal to 15MB', '15000');
        	if($max_photo_size * 1024 > $this->parseSize(ini_get('post_max_size'))) {
        		$this->error('Max photo size (' . (round($max_photo_size / 1000)) . 'M) cannot exceed php.ini `post_max_size` of ' . ini_get('post_max_size'));
        		exit;
        	}
        	$this->updateEnvFile('MAX_PHOTO_SIZE', $max_photo_size);

        	$max_caption_length = $this->ask('Max caption limit. Default to 500, max 5000.', '500');
        	if($max_caption_length > 5000) {
        		$this->error('Max caption length is 5000 characters.');
        		exit;
        	}
        	$this->updateEnvFile('MAX_CAPTION_LENGTH', $max_caption_length);

        	$max_album_length = $this->ask('Max photos allowed per album. Choose a value between 1 and 10.', '4');
        	if($max_album_length < 1) {
        		$this->error('Min album length is 1 photos per album.');
        		exit;
        	}
        	if($max_album_length > 10) {
        		$this->error('Max album length is 10 photos per album.');
        		exit;
        	}
        	$this->updateEnvFile('MAX_ALBUM_LENGTH', $max_album_length);
        }

        $this->updateEnvFile('APP_ENV', 'production');
        $this->postInstall();
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
        $this->line('');
        $this->info('We recommend running database migrations now, or you can do it manually later.');
        $confirm = $this->choice('Do you want to run the database migrations?', ['No', 'Yes'], 0);
        if($confirm === 'Yes') {
        	$this->callSilently('config:clear');
        	sleep(3);
        	$this->call('migrate', ['--force' => true]);
	        $this->callSilently('instance:actor');
	        $this->callSilently('passport:install');

	        $confirm = $this->choice('Do you want to create an admin account?', ['No', 'Yes'], 0);
	        if($confirm === 'Yes') {
	        	$this->call('user:create');
	        }
        } else {
        	$this->callSilently('config:cache');
        }

        $this->info('Pixelfed has been successfully installed!');
    }

    protected function parseSize($size) {
    	$unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    	$size = preg_replace('/[^0-9\.]/', '', $size);
    	if ($unit) {
    		return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    	}
    	else {
    		return round($size);
    	}
    }
}
