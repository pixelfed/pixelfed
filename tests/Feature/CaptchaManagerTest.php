<?php

namespace Tests\Feature;

use App\Services\Captcha\CapDriver;
use App\Services\Captcha\CaptchaManager;
use App\Services\Captcha\HCaptchaDriver;
use App\Services\Captcha\TurnstileDriver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaptchaManagerTest extends TestCase
{
    private function manager(): CaptchaManager
    {
        // Build a fresh manager so it re-reads the current config each time
        // (the base Manager caches resolved drivers internally).
        return new CaptchaManager($this->app);
    }

    #[Test]
    public function it_resolves_the_hcaptcha_driver_by_default(): void
    {
        config(['captcha.driver' => 'hcaptcha']);

        $driver = $this->manager()->active();

        $this->assertInstanceOf(HCaptchaDriver::class, $driver);
        $this->assertSame('hcaptcha', $driver->name());
        $this->assertSame('h-captcha-response', $driver->responseField());
    }

    #[Test]
    public function hcaptcha_is_configured_from_namespaced_keys(): void
    {
        config([
            'captcha.driver' => 'hcaptcha',
            'captcha.hcaptcha.secret' => 'a-real-secret',
            'captcha.hcaptcha.sitekey' => 'a-real-sitekey',
        ]);

        $this->assertTrue($this->manager()->active()->isConfigured());

        // Placeholder defaults should read as not configured.
        config([
            'captcha.hcaptcha.secret' => 'default_secret',
            'captcha.hcaptcha.sitekey' => 'default_sitekey',
        ]);
        $this->assertFalse($this->manager()->active()->isConfigured());
    }

    #[Test]
    public function it_resolves_the_turnstile_driver(): void
    {
        config(['captcha.driver' => 'turnstile']);

        $driver = $this->manager()->active();

        $this->assertInstanceOf(TurnstileDriver::class, $driver);
        $this->assertSame('turnstile', $driver->name());
        $this->assertSame('cf-turnstile-response', $driver->responseField());
    }

    #[Test]
    public function it_resolves_the_cap_driver(): void
    {
        config(['captcha.driver' => 'cap']);

        $driver = $this->manager()->active();

        $this->assertInstanceOf(CapDriver::class, $driver);
        $this->assertSame('cap', $driver->name());
        $this->assertSame('cap-token', $driver->responseField());
    }

    #[Test]
    public function it_lists_available_drivers(): void
    {
        $this->assertSame(['hcaptcha', 'turnstile', 'cap'], $this->manager()->available());
    }

    #[Test]
    public function active_on_requires_the_global_toggle(): void
    {
        config([
            'captcha.enabled' => false,
            'captcha.active.login' => true,
        ]);

        $this->assertFalse($this->manager()->activeOn('login'));
    }

    #[Test]
    public function active_on_honors_each_surface_flag(): void
    {
        config([
            'captcha.enabled' => true,
            'captcha.active.login' => true,
            'captcha.active.register' => false,
            'captcha.active.forgotpassword' => true,
            'captcha.active.password_reset' => false,
            'captcha.active.curated_register' => true,
        ]);

        $manager = $this->manager();

        $this->assertTrue($manager->activeOn('login'));
        $this->assertFalse($manager->activeOn('register'));
        $this->assertTrue($manager->activeOn('forgotpassword'));
        $this->assertFalse($manager->activeOn('password_reset'));
        $this->assertTrue($manager->activeOn('curated_register'));
    }

    #[Test]
    public function active_on_login_honors_surface_and_attempt_trigger(): void
    {
        // Surface directly active
        config([
            'captcha.enabled' => true,
            'captcha.active.login' => true,
            'captcha.triggers.login.enabled' => false,
        ]);
        $this->assertTrue($this->manager()->activeOnLogin());

        // Surface off, trigger disabled -> false
        config(['captcha.active.login' => false]);
        $this->assertFalse($this->manager()->activeOnLogin());

        // Trigger enabled but below threshold -> false
        config([
            'captcha.triggers.login.enabled' => true,
            'captcha.triggers.login.attempts' => 2,
        ]);
        $session = $this->app['session']->driver();
        request()->setLaravelSession($session);
        $session->put('login_attempts', 1);
        $this->assertFalse($this->manager()->activeOnLogin());

        // Trigger enabled and at threshold -> true
        $session->put('login_attempts', 2);
        $this->assertTrue($this->manager()->activeOnLogin());
    }

    #[Test]
    public function cap_widget_defaults_to_latest_when_no_version_set(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.endpoint' => 'https://cap.example.com/site-key/',
            'captcha.cap.widget_version' => null,
        ]);

        $scripts = $this->manager()->active()->scripts();

        $this->assertStringContainsString('@cap.js/widget@latest', $scripts);
    }

    #[Test]
    public function cap_widget_loads_from_cdn_with_pinned_version(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.endpoint' => 'https://cap.example.com/site-key/',
            'captcha.cap.widget_version' => '0.1.57',
        ]);

        $driver = $this->manager()->active();
        $scripts = $driver->scripts();

        $this->assertStringContainsString('cdn.jsdelivr.net/npm/@cap.js/widget@0.1.57', $scripts);
        // Must not reference self-hosted assets anymore.
        $this->assertStringNotContainsString('vendor/cap/', $scripts);

        $markup = $driver->render(['data-theme' => 'dark']);
        $this->assertStringContainsString('<cap-widget', $markup);
        $this->assertStringContainsString('data-cap-api-endpoint="https://cap.example.com/site-key/"', $markup);
        $this->assertStringContainsString('data-cap-hidden-field-name="cap-token"', $markup);
    }

    #[Test]
    public function turnstile_renders_widget_with_sitekey(): void
    {
        config([
            'captcha.driver' => 'turnstile',
            'captcha.turnstile.sitekey' => '0xTESTSITEKEY',
        ]);

        $markup = $this->manager()->active()->render();

        $this->assertStringContainsString('cf-turnstile', $markup);
        $this->assertStringContainsString('data-sitekey="0xTESTSITEKEY"', $markup);
    }
}
