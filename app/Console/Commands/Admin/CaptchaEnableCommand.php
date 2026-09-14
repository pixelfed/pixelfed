<?php

namespace App\Console\Commands\Admin;

use App\Services\ConfigCacheService;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

/**
 * Enables captcha and turns on the per-surface toggles.
 *
 * Because captcha settings are stored in the config-cache DB table (which
 * overrides env/config-file values), enabling captcha via .env alone has no
 * effect on an instance that already has rows. This command writes the correct
 * rows so the change takes effect immediately.
 */
final class CaptchaEnableCommand extends Command
{
    protected $signature = 'captcha:enable
        {--surfaces=* : Limit to specific surfaces (login, register, forgot_password, password_reset, forgot_email, curated_register). Defaults to all.}
        {--all-surfaces : Enable every surface (default when no --surfaces given)}';

    protected $description = 'Enable captcha and its per-surface toggles in the config cache';

    private const SURFACES = [
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

        $requested = (array) $this->option('surfaces');
        $surfaces = empty($requested) ? self::SURFACES : $requested;

        foreach ($surfaces as $surface) {
            if (! in_array($surface, self::SURFACES, true)) {
                warning("Skipping unknown surface: {$surface}");

                continue;
            }
            ConfigCacheService::put('captcha.active.'.$surface, true);
            info("captcha.active.{$surface} => true");
        }

        info('Done. Active driver: '.$driver);

        return self::SUCCESS;
    }
}
