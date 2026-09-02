<?php

namespace App\Console\Commands\Admin;

use App\Services\ConfigCacheService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;

#[Signature('app:captcha-toggle-command')]
#[Description('Command description')]
final class CaptchaToggleCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $captchaEnabled = (bool) config_cache('captcha.enabled');

        info($captchaEnabled ? 'Captcha is enabled' : 'Captcha is not enabled');

        if (! $captchaEnabled) {
            info('Enable the Captcha from the admin settings dashboard.');

            return;
        }

        $confirmed = confirm(
            label: 'Do you want to disable the captcha?',
            default: false,
            yes: 'Yes',
            no: 'No',
            hint: 'Select an option to proceed.'
        );

        if ($confirmed) {
            ConfigCacheService::put('captcha.enabled', false);
        }
    }
}
