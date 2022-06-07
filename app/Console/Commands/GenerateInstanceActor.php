<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\InstanceActor;
use Cache;

class GenerateInstanceActor extends Command
{
	protected $signature = 'instance:actor';
	protected $description = 'Generate instance actor';

	public function __construct()
	{
		parent::__construct();
	}

	public function handle()
	{
		if(Schema::hasTable('instance_actors') == false) {
			$this->line(' ');
			$this->error('Missing instance_actors table.');
			$this->info('Run "php artisan migrate" and try again.');
			$this->line(' ');
			exit;
		}

		if(InstanceActor::exists()) {
			$actor = InstanceActor::whereNotNull('public_key')
				->whereNotNull('private_key')
				->firstOrFail();
			Cache::rememberForever(InstanceActor::PKI_PUBLIC, function() use($actor) {
				return $actor->public_key;
			});

			Cache::rememberForever(InstanceActor::PKI_PRIVATE, function() use($actor) {
				return $actor->private_key;
			});
			$this->info('Instance actor succesfully generated. You do not need to run this command again.');
			return;
		}

		$pkiConfig = [
			'digest_alg'       => 'sha512',
			'private_key_bits' => 2048,
			'private_key_type' => OPENSSL_KEYTYPE_RSA,
		];
		$pki = openssl_pkey_new($pkiConfig);
		openssl_pkey_export($pki, $pki_private);
		$pki_public = openssl_pkey_get_details($pki);
		$pki_public = $pki_public['key'];

		$actor = new InstanceActor();
		$actor->public_key = $pki_public;
		$actor->private_key = $pki_private;
		$actor->save();

		Cache::rememberForever(InstanceActor::PKI_PUBLIC, function() use($actor) {
			return $actor->public_key;
		});

		Cache::rememberForever(InstanceActor::PKI_PRIVATE, function() use($actor) {
			return $actor->private_key;
		});

		$this->info('Instance actor succesfully generated. You do not need to run this command again.');

		return 0;
	}
}
