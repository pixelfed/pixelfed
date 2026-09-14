<?php

namespace Tests\Feature;

use App\Services\AdminSettingsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Exercises the admin read path (AdminSettingsService::getPlatform) that feeds
 * the captcha settings UI, including masking and provider-namespaced keys.
 */
class CaptchaAdminSettingsTest extends TestCase
{
    #[Test]
    public function platform_settings_expose_all_captcha_surface_toggles(): void
    {
        config([
            'captcha.enabled' => true,
            'captcha.driver' => 'turnstile',
            'captcha.active.login' => true,
            'captcha.active.register' => false,
            'captcha.active.forgot_password' => true,
            'captcha.active.password_reset' => false,
            'captcha.active.forgot_email' => true,
            'captcha.active.curated_register' => true,
        ]);

        $platform = AdminSettingsService::getPlatform();

        $this->assertTrue($platform['captcha_enabled']);
        $this->assertSame('turnstile', $platform['captcha_driver']);
        $this->assertTrue($platform['captcha_on_login']);
        $this->assertFalse($platform['captcha_on_register']);
        $this->assertTrue($platform['captcha_on_forgot_password']);
        $this->assertFalse($platform['captcha_on_password_reset']);
        $this->assertTrue($platform['captcha_on_forgot_email']);
        $this->assertTrue($platform['captcha_on_curated_register']);
    }

    #[Test]
    public function platform_settings_expose_cap_endpoint_and_sitekey(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => '3c87a0e810',
        ]);

        $platform = AdminSettingsService::getPlatform();

        $this->assertArrayHasKey('captcha_cap_endpoint', $platform);
        $this->assertArrayHasKey('captcha_cap_sitekey', $platform);
        $this->assertSame('https://cap.example.com', $platform['captcha_cap_endpoint']);
        $this->assertSame('3c87a0e810', $platform['captcha_cap_sitekey']);
    }

    #[Test]
    public function secrets_are_masked_and_sitekeys_are_not(): void
    {
        config([
            'captcha.hcaptcha.secret' => 'supersecretvalue',
            'captcha.hcaptcha.sitekey' => 'publicsitekey123',
            'captcha.turnstile.secret' => 'turnstilesecretvalue',
            'captcha.turnstile.sitekey' => '0xPUBLICKEY',
            'captcha.cap.secret' => 'capsecretvalue',
        ]);

        $platform = AdminSettingsService::getPlatform();

        // Secrets masked (contain the mask char, not the raw value).
        $this->assertStringContainsString('*', $platform['captcha_hcaptcha_secret']);
        $this->assertStringNotContainsString('supersecretvalue', $platform['captcha_hcaptcha_secret']);
        $this->assertStringContainsString('*', $platform['captcha_turnstile_secret']);
        $this->assertStringContainsString('*', $platform['captcha_cap_secret']);

        // Sitekeys are public and returned as-is (never masked) for every provider.
        $this->assertSame('publicsitekey123', $platform['captcha_hcaptcha_sitekey']);
        $this->assertSame('0xPUBLICKEY', $platform['captcha_turnstile_sitekey']);
    }

    #[Test]
    public function mask_secret_tolerates_empty_and_null_values(): void
    {
        config([
            'captcha.hcaptcha.secret' => null,
            'captcha.turnstile.secret' => '',
        ]);

        // Should not throw even when secrets are null/empty.
        $platform = AdminSettingsService::getPlatform();

        $this->assertArrayHasKey('captcha_hcaptcha_secret', $platform);
        $this->assertArrayHasKey('captcha_turnstile_secret', $platform);
    }
}
