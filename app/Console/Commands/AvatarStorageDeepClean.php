<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Cache;
use Storage;
use App\Avatar;
use App\Jobs\AvatarPipeline\AvatarStorageCleanup;

class AvatarStorageDeepClean extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatar:storage-deep-clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup avatar storage';

    protected $shouldKeepRunning = true;
    protected $counter = 0;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('       ____  _           ______         __  ');
        $this->info('      / __ \(_)  _____  / / __/__  ____/ /  ');
        $this->info('     / /_/ / / |/_/ _ \/ / /_/ _ \/ __  /   ');
        $this->info('    / ____/ />  </  __/ / __/  __/ /_/ /    ');
        $this->info('   /_/   /_/_/|_|\___/_/_/  \___/\__,_/     ');
        $this->info(' ');
        $this->info('    Pixelfed Avatar Deep Cleaner');
        $this->line(' ');
        $this->info('    Purge/delete old and outdated avatars from remote accounts');
        $this->line(' ');

        $storage = [
            'cloud' => boolval(config_cache('pixelfed.cloud_storage')),
            'local' => boolval(config_cache('federation.avatars.store_local'))
        ];

        if(!$storage['cloud'] && !$storage['local']) {
            $this->error('Remote avatars are not cached locally, there is nothing to purge. Aborting...');
            exit;
        }

        $start = 0;

        if(!$this->confirm('Are you sure you want to proceed?')) {
            $this->error('Aborting...');
            exit;
        }

        if(!$this->activeCheck()) {
            $this->info('Found existing deep cleaning job');
            if(!$this->confirm('Do you want to continue where you left off?')) {
                $this->error('Aborting...');
                exit;
            } else {
                $start = Cache::has('cmd:asdp') ? (int) Cache::get('cmd:asdp') : (int) Storage::get('avatar-deep-clean.json');

                if($start && $start < 1 || $start > PHP_INT_MAX) {
                    $this->error('Error fetching cached value');
                    $this->error('Aborting...');
                    exit;
                }
            }
        }

        $count = Avatar::whereNotNull('cdn_url')->where('is_remote', true)->where('id', '>', $start)->count();
        $bar = $this->output->createProgressBar($count);

        foreach(Avatar::whereNotNull('cdn_url')->where('is_remote', true)->where('id', '>', $start)->lazyById(10, 'id') as $avatar) {
            usleep(random_int(50, 1000));
            $this->counter++;
            $this->handleAvatar($avatar);
            $bar->advance();
        }
        $bar->finish();
    }

    protected function updateCache($id)
    {
        Cache::put('cmd:asdp', $id);
        if($this->counter % 5 === 0) {
            Storage::put('avatar-deep-clean.json', $id);
        }
    }

    protected function activeCheck()
    {
        if(Storage::exists('avatar-deep-clean.json') || Cache::has('cmd:asdp')) {
            return false;
        }

        return true;
    }

    protected function handleAvatar($avatar)
    {
        $this->updateCache($avatar->id);
        $queues = ['feed', 'mmo', 'feed', 'mmo', 'feed', 'feed', 'mmo', 'low'];
        $queue = $queues[random_int(0, 7)];
        AvatarStorageCleanup::dispatch($avatar)->onQueue($queue);
    }
}
