<?php

namespace Tests\Feature;

use App\Services\Captcha\CapDriver;
use App\Services\Captcha\CaptchaManager;
use App\Services\Captcha\HCaptchaDriver;
use App\Services\Captcha\TurnstileDriver;
use PHPUnit\Framework\Attributes\DataProvider;
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

    // ---------------------------------------------------------------------
    // Driver resolution
    // ---------------------------------------------------------------------

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
    public function it_falls_back_to_hcaptcha_for_unknown_or_empty_driver(): void
    {
        config(['captcha.driver' => null]);
        $this->assertInstanceOf(HCaptchaDriver::class, $this->manager()->active());
    }

    #[Test]
    #[DataProvider('driverProvider')]
    public function it_resolves_each_driver_and_field(string $driver, string $class, string $field): void
    {
        config(['captcha.driver' => $driver]);

        $resolved = $this->manager()->active();

        $this->assertInstanceOf($class, $resolved);
        $this->assertSame($driver, $resolved->name());
        $this->assertSame($field, $resolved->responseField());
    }

    public static function driverProvider(): array
    {
        return [
            'hcaptcha' => ['hcaptcha', HCaptchaDriver::class, 'h-captcha-response'],
            'turnstile' => ['turnstile', TurnstileDriver::class, 'cf-turnstile-response'],
            'cap' => ['cap', CapDriver::class, 'cap-token'],
        ];
    }

    #[Test]
    public function it_lists_available_drivers(): void
    {
        $this->assertSame(['hcaptcha', 'turnstile', 'cap'], $this->manager()->available());
    }

    #[Test]
    public function rules_use_the_active_drivers_response_field(): void
    {
        config(['captcha.driver' => 'cap', 'captcha.cap.token_field' => 'cap-token']);
        $this->assertSame(['cap-token' => 'required|captcha_verify'], $this->manager()->rules());

        config(['captcha.driver' => 'turnstile']);
        $this->assertArrayHasKey('cf-turnstile-response', $this->manager()->rules());
    }

    // ---------------------------------------------------------------------
    // enabled() / activeOn()
    // ---------------------------------------------------------------------

    #[Test]
    public function enabled_reflects_the_global_toggle(): void
    {
        config(['captcha.enabled' => true]);
        $this->assertTrue($this->manager()->enabled());

        config(['captcha.enabled' => false]);
        $this->assertFalse($this->manager()->enabled());
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
            'captcha.active.forgot_password' => true,
            'captcha.active.password_reset' => false,
            'captcha.active.forgot_email' => true,
            'captcha.active.curated_register' => true,
        ]);

        $manager = $this->manager();

        $this->assertTrue($manager->activeOn('login'));
        $this->assertFalse($manager->activeOn('register'));
        $this->assertTrue($manager->activeOn('forgot_password'));
        $this->assertFalse($manager->activeOn('password_reset'));
        $this->assertTrue($manager->activeOn('forgot_email'));
        $this->assertTrue($manager->activeOn('curated_register'));
    }

    #[Test]
    public function active_on_returns_false_for_an_unknown_surface(): void
    {
        config(['captcha.enabled' => true]);
        $this->assertFalse($this->manager()->activeOn('does_not_exist'));
    }

    // ---------------------------------------------------------------------
    // hCaptcha driver
    // ---------------------------------------------------------------------

    #[Test]
    public function hcaptcha_is_configured_from_namespaced_keys(): void
    {
        config([
            'captcha.driver' => 'hcaptcha',
            'captcha.hcaptcha.secret' => 'a-real-secret',
            'captcha.hcaptcha.sitekey' => 'a-real-sitekey',
        ]);

        $this->assertTrue($this->manager()->active()->isConfigured());
    }

    #[Test]
    public function hcaptcha_is_not_configured_with_placeholder_defaults(): void
    {
        config([
            'captcha.driver' => 'hcaptcha',
            'captcha.hcaptcha.secret' => 'default_secret',
            'captcha.hcaptcha.sitekey' => 'default_sitekey',
        ]);

        $this->assertFalse($this->manager()->active()->isConfigured());
    }

    #[Test]
    public function hcaptcha_is_not_configured_when_keys_are_empty(): void
    {
        config([
            'captcha.driver' => 'hcaptcha',
            'captcha.hcaptcha.secret' => '',
            'captcha.hcaptcha.sitekey' => '',
        ]);

        $this->assertFalse($this->manager()->active()->isConfigured());
    }

    #[Test]
    public function hcaptcha_renders_widget_with_sitekey(): void
    {
        config([
            'captcha.driver' => 'hcaptcha',
            'captcha.hcaptcha.sitekey' => 'my-h-sitekey',
        ]);

        $markup = $this->manager()->active()->render(['data-theme' => 'dark']);

        $this->assertStringContainsString('class="h-captcha"', $markup);
        $this->assertStringContainsString('data-sitekey="my-h-sitekey"', $markup);
        $this->assertStringContainsString('data-theme="dark"', $markup);
    }

    #[Test]
    public function hcaptcha_scripts_reference_hcaptcha_cdn(): void
    {
        config(['captcha.driver' => 'hcaptcha', 'captcha.hcaptcha.lang' => null]);

        $scripts = $this->manager()->active()->scripts();

        $this->assertStringContainsString('js.hcaptcha.com/1/api.js', $scripts);
        $this->assertStringNotContainsString('hl=', $scripts);
    }

    #[Test]
    public function hcaptcha_scripts_include_locale_when_set(): void
    {
        config(['captcha.driver' => 'hcaptcha', 'captcha.hcaptcha.lang' => 'fr']);

        $this->assertStringContainsString('hl=fr', $this->manager()->active()->scripts());
    }

    // ---------------------------------------------------------------------
    // Turnstile driver
    // ---------------------------------------------------------------------

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

    #[Test]
    public function turnstile_render_escapes_attributes(): void
    {
        config([
            'captcha.driver' => 'turnstile',
            'captcha.turnstile.sitekey' => '0xKEY',
        ]);

        $markup = $this->manager()->active()->render(['data-theme' => '"><script>x']);

        $this->assertStringNotContainsString('<script>x', $markup);
        $this->assertStringContainsString('&lt;script&gt;', $markup);
    }

    #[Test]
    public function turnstile_scripts_reference_cloudflare(): void
    {
        config(['captcha.driver' => 'turnstile']);
        $scripts = $this->manager()->active()->scripts();

        $this->assertStringContainsString('challenges.cloudflare.com/turnstile/v0/api.js', $scripts);
        // Warms the connection to Cloudflare's challenge origin before the
        // widget script fetches from it (crossorigin, since it's cross-origin).
        $this->assertStringContainsString('<link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin>', $scripts);
    }

    #[Test]
    public function turnstile_is_configured_only_with_both_keys(): void
    {
        config(['captcha.driver' => 'turnstile']);

        config(['captcha.turnstile.sitekey' => null, 'captcha.turnstile.secret' => null]);
        $this->assertFalse($this->manager()->active()->isConfigured());

        config(['captcha.turnstile.sitekey' => 'k', 'captcha.turnstile.secret' => null]);
        $this->assertFalse($this->manager()->active()->isConfigured());

        config(['captcha.turnstile.sitekey' => 'k', 'captcha.turnstile.secret' => 's']);
        $this->assertTrue($this->manager()->active()->isConfigured());
    }

    // ---------------------------------------------------------------------
    // Cap driver — endpoint composition (base URL + sitekey)
    // ---------------------------------------------------------------------

    #[Test]
    public function cap_composes_the_api_endpoint_from_base_url_and_sitekey(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => '3c87a0e810',
        ]);

        $this->assertSame(
            'https://cap.example.com/3c87a0e810/',
            $this->manager()->active()->apiEndpoint()
        );
    }

    #[Test]
    public function cap_endpoint_composition_normalizes_slashes(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.endpoint' => 'https://cap.example.com/',
            'captcha.cap.sitekey' => '/abc/',
        ]);

        $this->assertSame(
            'https://cap.example.com/abc/',
            $this->manager()->active()->apiEndpoint()
        );
    }

    #[Test]
    public function cap_api_endpoint_is_empty_when_pieces_are_missing(): void
    {
        config(['captcha.driver' => 'cap']);

        config(['captcha.cap.endpoint' => 'https://cap.example.com', 'captcha.cap.sitekey' => null]);
        $this->assertSame('', $this->manager()->active()->apiEndpoint());

        config(['captcha.cap.endpoint' => null, 'captcha.cap.sitekey' => 'abc']);
        $this->assertSame('', $this->manager()->active()->apiEndpoint());
    }

    #[Test]
    public function cap_is_configured_only_with_endpoint_sitekey_and_secret(): void
    {
        config(['captcha.driver' => 'cap']);

        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => null,
            'captcha.cap.secret' => 'sk',
        ]);
        $this->assertFalse($this->manager()->active()->isConfigured());

        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
        ]);
        $this->assertTrue($this->manager()->active()->isConfigured());
    }

    #[Test]
    public function cap_render_uses_composed_endpoint_and_token_field(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.token_field' => 'cap-token',
        ]);

        $markup = $this->manager()->active()->render(['data-theme' => 'dark']);

        $this->assertStringContainsString('<cap-widget', $markup);
        $this->assertStringContainsString('data-cap-api-endpoint="https://cap.example.com/abc/"', $markup);
        $this->assertStringContainsString('data-cap-hidden-field-name="cap-token"', $markup);
        $this->assertStringContainsString('data-theme="dark"', $markup);
    }

    #[Test]
    public function cap_respects_a_custom_token_field(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.token_field' => 'my-cap-field',
        ]);

        $driver = $this->manager()->active();

        $this->assertSame('my-cap-field', $driver->responseField());
        $this->assertStringContainsString('data-cap-hidden-field-name="my-cap-field"', $driver->render());
    }

    // ---------------------------------------------------------------------
    // Cap driver — CDN widget script
    // ---------------------------------------------------------------------

    #[Test]
    public function cap_widget_defaults_to_latest_when_no_version_set(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.widget_version' => null,
        ]);

        $this->assertStringContainsString(
            '@cap.js/widget@latest',
            $this->manager()->active()->scripts()
        );
    }

    #[Test]
    public function cap_widget_defaults_to_latest_when_version_is_blank(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.widget_version' => '   ',
        ]);

        $this->assertStringContainsString(
            '@cap.js/widget@latest',
            $this->manager()->active()->scripts()
        );
    }

    #[Test]
    public function cap_widget_loads_from_cdn_with_pinned_version(): void
    {
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.widget_version' => '0.1.57',
        ]);

        $scripts = $this->manager()->active()->scripts();

        $this->assertStringContainsString('cdn.jsdelivr.net/npm/@cap.js/widget@0.1.57', $scripts);
        // Must not reference the old self-hosted assets.
        $this->assertStringNotContainsString('vendor/cap/', $scripts);
    }
}
