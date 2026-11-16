<?php

namespace App\Console\Commands\Install;

use App\Models\InstanceActor;
use App\Util\ActivityPub\KeyPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

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
        if (Schema::hasTable('instance_actors') == false) {
            $this->line(' ');
            $this->error('Missing instance_actors table.');
            $this->info('Run "php artisan migrate" and try again.');
            $this->line(' ');
            exit;
        }

        if (InstanceActor::exists()) {
            $actor = InstanceActor::whereNotNull('public_key')
                ->whereNotNull('private_key')
                ->firstOrFail();
            Cache::rememberForever(InstanceActor::PKI_PUBLIC, function () use ($actor) {
                return $actor->public_key;
            });

            Cache::rememberForever(InstanceActor::PKI_PRIVATE, function () use ($actor) {
                return $actor->private_key;
            });
            $this->info('Instance actor succesfully generated. You do not need to run this command again.');

            return null;
        }

        $keyPair = KeyPair::generate();

        $actor = new InstanceActor;
        $actor->public_key = $keyPair['public'];
        $actor->private_key = $keyPair['private'];
        $actor->save();

        Cache::rememberForever(InstanceActor::PKI_PUBLIC, function () use ($actor) {
            return $actor->public_key;
        });

        Cache::rememberForever(InstanceActor::PKI_PRIVATE, function () use ($actor) {
            return $actor->private_key;
        });

        $this->info('Instance actor succesfully generated. You do not need to run this command again.');

        return 0;
    }
}
