<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\User;

class InstallController extends Controller
{
    public function index(Request $request)
    {
    	abort_if(file_exists(base_path('.env')), 404);
    	return view('installer.index');
    }

    public function getRequirements()
    {
    	abort_if(file_exists(base_path('.env')), 404);
    	$reqs = [];

    	$phpExtensions = [
    		'bcmath',
    		'ctype',
    		'curl',
    		'intl',
    		'json',
    		'mbstring',
    		'openssl',
    		'gd',
    		'redis',
    		'pdo_mysql',
    		'pdo_pgsql'
    	];

    	$dirs = [
    		'bootstrap',
    		'storage'
    	];

    	$reqs['php'] = [
    		'version' => PHP_VERSION,
    		'supported' => (bool) version_compare(PHP_VERSION, '7.4'),
    		'min_version' => '7.4',
    		'memory_limit' => [
    			'recommended' => '256M',
    			'actual' => ini_get('memory_limit'),
    		],
    		'upload_max_filesize' => [
    			'recommended' => '15M',
    			'actual' => ini_get('upload_max_filesize')
    		],
    		'post_max_size' => [
    			'recommended' => '15M',
    			'actual' => ini_get('post_max_size')
    		]
    	];

    	$reqs['php']['extensions'] = collect($phpExtensions)
    		->map(function($ext) {
    			return [ 'name' => $ext, 'loaded' => extension_loaded($ext) ];
    		});

    	$reqs['permissions'] = collect($dirs)
    		->map(function($path) {
    			return [
    				'name' => $path,
    				'writable' => is_writable(base_path($path)),
    				'mode' => substr(sprintf('%o', fileperms(base_path($path))), -4),
    				'path' => base_path($path)
    			];
    		});
    	return $reqs;
    }

    public function store(Request $request)
    {
    	abort_if(file_exists(base_path('.env')), 404, 'The .env configuration file already exists.');
    	return $this->checkPermissions($request);
    }

    protected function checkPermissions($request)
    {
    	abort_if(file_exists(base_path('.env')), 404);

    	if( file_exists(base_path('.env'))) {
    		return response('Found existing .env file, aborting installation', 400);
    	}

    	if( !is_writable(base_path('storage/')) ) {
    		return response('Permission Problem(s), cannot write to bootstrap or storage directories', 400);
    	}
    	return $this->checkDatabase($request);
    }

    protected function checkDatabase($request)
    {
    	abort_if(file_exists(base_path('.env')), 404);

		$driver = $request->input('db_driver', 'mysql');
		$name = $request->input('db_name', 'pixelfed');
		$host = $request->input('db_host', 'localhost');
		$username = $request->input('db_username');
		$password = $request->input('db_password');
		$dsn = "{$driver}:dbname={$name};host={$host}";
		$dbh = new \PDO($dsn, $username, $password);

		try {
			$dbh->query('SELECT count(*) from INFORMATION_SCHEMA.TABLES');
		} catch (\PDOException $e) {
			return response($e, 400);
		}

		$this->createConfiguration($request);
    }

    protected function createConfiguration($request)
    {
    	abort_if(file_exists(base_path('.env')), 404);
    	$source = base_path('.env.example');
    	$target = base_path('.env');
    	@copy($source, $target);

    	$this->updateConfig('APP_URL=http://localhost', 'APP_URL=https://'.$request->input('domain'));
    	$this->updateConfig('APP_NAME="Pixelfed Prod"', 'APP_NAME="'.$request->input('name').'"');
    	$this->updateConfig('APP_DOMAIN="localhost"', 'APP_DOMAIN="'.$request->input('domain').'"');
    	$this->updateConfig('ADMIN_DOMAIN="localhost"', 'ADMIN_DOMAIN="'.$request->input('domain').'"');
    	$this->updateConfig('SESSION_DOMAIN="localhost"', 'SESSION_DOMAIN="'.$request->input('domain').'"');

    	$this->updateConfig('DB_CONNECTION=mysql', 'DB_CONNECTION='.$request->input('db_driver'));
    	$this->updateConfig('DB_HOST=127.0.0.1', 'DB_HOST='.$request->input('db_host'));
    	$this->updateConfig('DB_PORT=3306', 'DB_PORT='.$request->input('db_port'));
    	$this->updateConfig('DB_DATABASE=pixelfed', 'DB_DATABASE='.$request->input('db_name'));
    	$this->updateConfig('DB_USERNAME=pixelfed', 'DB_USERNAME='.$request->input('db_username'));
    	$this->updateConfig('DB_PASSWORD=pixelfed', 'DB_PASSWORD='.$request->input('db_password'));

    	$this->updateConfig('CACHE_DRIVER=redis', 'CACHE_DRIVER='.$request->input('cache_driver'));
    	$this->updateConfig('QUEUE_DRIVER=redis', 'QUEUE_DRIVER='.$request->input('queue_driver'));
    	$this->updateConfig('REDIS_SCHEME=tcp', 'REDIS_SCHEME='.$request->input('redis_scheme'));
    	$this->updateConfig('REDIS_HOST=127.0.0.1', 'REDIS_HOST='.$request->input('redis_host'));
    	$this->updateConfig('REDIS_PORT=6379', 'REDIS_PORT='.$request->input('redis_port'));
    	$this->updateConfig('REDIS_PASSWORD=null', 'REDIS_PASSWORD='.$request->input('redis_password'));

    	$this->updateConfig('ACTIVITY_PUB=false', 'ACTIVITY_PUB=' .($request->input('features.activitypub') ? 'true' : 'false'));
    	$this->updateConfig('AP_INBOX=false', 'AP_INBOX=' .($request->input('features.activitypub') ? 'true' : 'false'));
    	$this->updateConfig('AP_REMOTE_FOLLOW=false', 'AP_REMOTE_FOLLOW=' .($request->input('features.activitypub') ? 'true' : 'false'));
    	$this->updateConfig('OPEN_REGISTRATION=true', 'OPEN_REGISTRATION=' .($request->input('features.open_registration') ? 'true' : 'false'));

    	$this->updateConfig('ENFORCE_EMAIL_VERIFICATION=true', 'ENFORCE_EMAIL_VERIFICATION=' .($request->input('mail_address_verify') ? 'true' : 'false'));
    	$this->updateConfig('PF_OPTIMIZE_IMAGES=true', 'PF_OPTIMIZE_IMAGES=' .($request->input('optimize_media') ? 'true' : 'false'));
    	$this->updateConfig('MAX_PHOTO_SIZE=15000', 'MAX_PHOTO_SIZE=' .($request->input('max_upload_size') * 1000));
    	$this->updateConfig('MEDIA_TYPES=image/jpeg,image/png,image/gif', 'MEDIA_TYPES=' .implode(',', $request->input('mime_types')));
    	$this->updateConfig('OAUTH_ENABLED=true', 'OAUTH_ENABLED=true');

    	if($request->input('optimize_media') == true) {
    		$this->updateConfig('IMAGE_QUALITY=80', 'IMAGE_QUALITY=' .$request->input('image_quality'));
    	}

    	sleep(1);
    	Artisan::call('config:cache');
    	sleep(1);
    	Artisan::call('key:generate --force');
    	sleep(1);
    	Artisan::call('migrate --force');
  		sleep(1);
    	Artisan::call('config:cache');

    	if($request->has('features.activitypub') && $request->input('features.activitypub') == true) {
    		Artisan::call('instance:actor');
    	}

    	if($request->filled(['admin_username', 'admin_password', 'admin_email'])) {
			$user = new User;
			$user->username = $request->input('admin_username', 'admin');
			$user->name = $request->input('admin_username', 'admin');
			$user->email = $request->input('admin_email');
			$user->password = bcrypt($request->input('admin_password'));
			$user->is_admin = true;
			$user->email_verified_at = now();
			$user->save();
    	}
    }

    protected function updateConfig($key, $value)
    {
    	$f = file_get_contents(base_path('.env'));
    	if(strpos($f, $key) !== false) {
    		$u = str_replace($key, $value, $f);
    	} else {
    		$u = $f;
    		$u .= $value . PHP_EOL;
    	}
    	sleep(1);
    	file_put_contents(base_path('.env'), $u);
    }

    public function precheckDatabase(Request $request)
    {
    	$driver = $request->input('db_driver', 'mysql');
		$name = $request->input('db_name', 'pixelfed');
		$host = $request->input('db_host', 'localhost');
		$username = $request->input('db_username');
		$password = $request->input('db_password');
		$dsn = "{$driver}:dbname={$name};host={$host}";
		$dbh = new \PDO($dsn, $username, $password);

		try {
			$dbh->query('SELECT count(*) from INFORMATION_SCHEMA.TABLES');
		} catch (\PDOException $e) {
			return response($e, 400);
		}
    }
}
