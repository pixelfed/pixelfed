<?php

namespace App\Services\Captcha;

use App\Contracts\CaptchaDriver;
use Illuminate\Support\Manager;

/**
 * Resolves the active captcha provider based on the "captcha.driver" config
 * value and proxies the provider-agnostic operations to it.
 *
 * @method string name()
 * @method bool isConfigured()
 * @method string responseField()
 * @method bool verify(array $input)
 * @method string render(array $attributes = [])
 * @method string scripts()
 */
class CaptchaManager extends Manager
{
    /**
     * The default driver name, resolved from config. Falls back to hcaptcha to
     * preserve existing behavior for instances that never set captcha.driver.
     */
    public function getDefaultDriver(): string
    {
        return (string) (config_cache('captcha.driver') ?: config('captcha.driver', 'hcaptcha'));
    }

    public function createHcaptchaDriver(): CaptchaDriver
    {
        return new HCaptchaDriver;
    }

    public function createTurnstileDriver(): CaptchaDriver
    {
        return new TurnstileDriver;
    }

    public function createCapDriver(): CaptchaDriver
    {
        return new CapDriver;
    }

    /**
     * The active driver instance.
     */
    public function active(): CaptchaDriver
    {
        return $this->driver();
    }

    /**
     * Whether captcha is globally enabled for this instance.
     */
    public function enabled(): bool
    {
        return (bool) config_cache('captcha.enabled');
    }

    /**
     * Whether captcha should be enforced on a given surface.
     *
     * Requires the global toggle plus the per-surface "active" flag. Supported
     * surfaces: login, register, forgotpassword, password_reset,
     * curated_register.
     */
    public function activeOn(string $surface): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return (bool) config_cache('captcha.active.'.$surface);
    }

    /**
     * Whether the login form should show/enforce a captcha right now.
     *
     * True when the login surface is active, or when the failed-login trigger
     * has reached its configured attempt threshold for the current session.
     */
    public function activeOnLogin(): bool
    {
        if ($this->activeOn('login')) {
            return true;
        }

        if (! (bool) config_cache('captcha.triggers.login.enabled')) {
            return false;
        }

        $request = request();
        if (! $request->hasSession()) {
            return false;
        }

        $session = $request->session();

        return $session->has('login_attempts')
            && $session->get('login_attempts') >= config('captcha.triggers.login.attempts');
    }

    /**
     * List of supported driver machine names.
     *
     * @return array<int, string>
     */
    public function available(): array
    {
        return ['hcaptcha', 'turnstile', 'cap'];
    }

    /**
     * Validation rules for the active driver, keyed by its response field.
     *
     * Merge the result into a controller's rule set to enforce captcha with
     * whatever provider is currently selected.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            $this->active()->responseField() => 'required|captcha_verify',
        ];
    }
}
