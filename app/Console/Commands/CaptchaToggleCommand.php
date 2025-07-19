<?php

namespace App\Console\Commands;

use App\Services\ConfigCacheService;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;

class CaptchaToggleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:captcha-toggle-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
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
