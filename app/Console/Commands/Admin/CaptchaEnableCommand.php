<?php

namespace App\Console\Commands\Admin;

use App\Services\ConfigCacheService;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

/**
 * Enables captcha and turns on the per-page toggles.
 */
final class CaptchaEnableCommand extends Command
{
    protected $signature = 'captcha:enable
        {--pages=* : Limit to specific pages (login, register, forgot_password, password_reset, forgot_email, curated_register). Defaults to all.}
        {--all-pages : Enable every page (default when no --pages given)}';

    protected $description = 'Enable captcha and its per-page toggles in the config cache';

    private const array PAGES = [
        'login',
        'register',
        'forgot_password',
        'password_reset',
        'forgot_email',
        'curated_register',
    ];

    public function handle(): int
    {
        $driver = config_cache('captcha.driver') ?: config('captcha.driver', 'hcaptcha');

        if (! app('captcha.manager')->driver($driver)->isConfigured()) {
            warning("The active captcha driver [{$driver}] is not fully configured.");
            warning('Set its credentials in the admin panel or .env before enabling, or the widget will not verify.');
        }

        ConfigCacheService::put('captcha.enabled', true);
        info('captcha.enabled => true');

        $requested = (array) $this->option('pages');
        $pages = empty($requested) ? self::PAGES : $requested;

        foreach ($pages as $page) {
            if (! in_array($page, self::PAGES, true)) {
                warning("Skipping unknown page: {$page}");

                continue;
            }
            ConfigCacheService::put('captcha.active.'.$page, true);
            info("captcha.active.{$page} => true");
        }

        info('Done. Active driver: '.$driver);

        return self::SUCCESS;
    }
}
