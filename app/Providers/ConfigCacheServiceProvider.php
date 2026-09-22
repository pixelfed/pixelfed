<?php

namespace App\Providers;

use App\Services\Config\EnvConfigValidator;
use App\Services\ConfigCacheService;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ConfigCacheServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        EnvConfigValidator::validateBootEnv();

        // `optimize` caches config via callSilently, which emits no nested
        // config:cache event, so we react to both to catch every rebuild.
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if (! in_array($event->command, ['config:cache', 'optimize'], true)) {
                return;
            }

            if (! ConfigCacheService::syncEnabled()) {
                return;
            }

            $exitCode = Artisan::call('admin:pixelfed-config-cache-sync');

            $event->output->writeln($exitCode === 0
                ? "<info>[{$event->command}] Pixelfed config-cache sync succeeded.</info>"
                : "<error>[{$event->command}] Pixelfed config-cache sync failed (exit {$exitCode}).</error>");
        });
    }
}
