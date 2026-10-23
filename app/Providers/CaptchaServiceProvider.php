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
        $this->registerValidationRule();
        $this->registerBladeDirectives();
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
