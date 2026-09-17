<?php

namespace App\Providers;

use App\Console\Commands\Admin\PixelfedConfigCacheSync;
use App\Services\Config\EnvConfigValidator;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ConfigCacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        EnvConfigValidator::validateBootEnv();

        // `optimize` caches config via callSilently, which emits no nested
        // config:cache event, so we react to both to catch every rebuild.
        Event::listen(CommandFinished::class, function (CommandFinished $event) {
            if (! in_array($event->command, ['config:cache', 'optimize'], true)) {
                return;
            }

            if (! PixelfedConfigCacheSync::syncEnabled()) {
                return;
            }

            $exitCode = Artisan::call('admin:pixelfed-config-cache-sync');

            $event->output->writeln($exitCode === 0
                ? '<info>[config:cache] config-cache sync succeeded.</info>'
                : "<error>[config:cache] config-cache sync failed (exit {$exitCode}).</error>");
        });
    }
}
