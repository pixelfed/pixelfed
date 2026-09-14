<?php

namespace App\Providers;

use App\Services\Captcha\CaptchaManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('captcha.manager', fn ($app) => new CaptchaManager($app));
        $this->app->alias('captcha.manager', CaptchaManager::class);
    }

    public function boot(): void
    {
        $this->hydrateHcaptchaConfig();
        $this->registerValidationRule();
        $this->registerBladeDirectives();
    }

    /**
     * The buzz/laravel-h-captcha package reads its config at the TOP LEVEL of
     * the "captcha" config (captcha.secret, captcha.sitekey, captcha.http_client,
     * captcha.options, captcha.attributes) via the plain config repository — it
     * does not consult the DB-backed config cache.
     *
     * We keep all hCaptcha config under captcha.hcaptcha.*, so hydrate the
     * top-level keys the package expects here. Secret/sitekey use config_cache()
     * so admin-panel values (not just .env) are honored; the static widget
     * options come straight from the config file.
     */
    private function hydrateHcaptchaConfig(): void
    {
        $secret = config_cache('captcha.hcaptcha.secret');
        $sitekey = config_cache('captcha.hcaptcha.sitekey');

        if (! empty($secret)) {
            config(['captcha.secret' => $secret]);
        }

        if (! empty($sitekey)) {
            config(['captcha.sitekey' => $sitekey]);
        }

        config([
            'captcha.http_client' => config('captcha.hcaptcha.http_client'),
            'captcha.options' => config('captcha.hcaptcha.options'),
            'captcha.attributes' => config('captcha.hcaptcha.attributes'),
        ]);
    }

    /**
     * Driver-agnostic validation rule.
     *
     * Usage: 'some_field' => 'captcha_verify'
     *
     * It ignores $value (each provider uses a different field name) and instead
     * validates the whole request against the active driver's own response field.
     */
    private function registerValidationRule(): void
    {
        Validator::extend('captcha_verify', function ($attribute, $value, $parameters, $validator) {
            /** @var CaptchaManager $manager */
            $manager = app('captcha.manager');

            return $manager->active()->verify($validator->getData());
        }, 'The captcha verification failed. Please try again.');
    }

    private function registerBladeDirectives(): void
    {
        // @captcha or @captcha(['data-theme' => 'dark']) -> renders active widget
        Blade::directive('captcha', function ($expression) {
            $args = trim((string) $expression) === '' ? '[]' : $expression;

            return "<?php echo app('captcha.manager')->active()->render($args); ?>";
        });

        // @captchaScripts -> any <script>/<link> the active widget needs
        Blade::directive('captchaScripts', function () {
            return "<?php echo app('captcha.manager')->active()->scripts(); ?>";
        });
    }
}
