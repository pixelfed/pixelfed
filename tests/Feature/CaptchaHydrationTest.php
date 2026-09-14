<?php

namespace Tests\Feature;

use App\Providers\CaptchaServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The buzz/laravel-h-captcha package reads config('captcha.secret') and
 * config('captcha.sitekey') via the plain config repository (not the DB config
 * cache). CaptchaServiceProvider::boot() must hydrate those runtime values from
 * the admin-managed captcha.hcaptcha.* keys so admin-panel credentials work.
 *
 * Note: the test env sets ENABLE_CONFIG_CACHE=false, so config_cache() reads
 * straight from config(), which is what we seed here.
 */
class CaptchaHydrationTest extends TestCase
{
    private function bootProvider(): void
    {
        (new CaptchaServiceProvider($this->app))->boot();
    }

    #[Test]
    public function it_hydrates_buzz_config_from_admin_managed_hcaptcha_keys(): void
    {
        config([
            'captcha.hcaptcha.secret' => 'admin-saved-secret',
            'captcha.hcaptcha.sitekey' => 'admin-saved-sitekey',
            // Stale/.env-only values the buzz package would otherwise read.
            'captcha.secret' => 'default_secret',
            'captcha.sitekey' => 'default_sitekey',
        ]);

        $this->bootProvider();

        $this->assertSame('admin-saved-secret', config('captcha.secret'));
        $this->assertSame('admin-saved-sitekey', config('captcha.sitekey'));
    }

    #[Test]
    public function it_hydrates_widget_options_from_the_hcaptcha_block(): void
    {
        config([
            'captcha.hcaptcha.http_client' => 'Some\\Custom\\Client',
            'captcha.hcaptcha.options' => ['multiple' => true, 'lang' => 'fr'],
            'captcha.hcaptcha.attributes' => ['theme' => 'dark'],
            // Ensure the top-level keys start out different.
            'captcha.http_client' => null,
            'captcha.options' => null,
            'captcha.attributes' => null,
        ]);

        $this->bootProvider();

        $this->assertSame('Some\\Custom\\Client', config('captcha.http_client'));
        $this->assertSame(['multiple' => true, 'lang' => 'fr'], config('captcha.options'));
        $this->assertSame(['theme' => 'dark'], config('captcha.attributes'));
    }

    #[Test]
    public function it_leaves_buzz_config_untouched_when_hcaptcha_keys_are_empty(): void
    {
        config([
            'captcha.hcaptcha.secret' => '',
            'captcha.hcaptcha.sitekey' => null,
            'captcha.secret' => 'env_secret',
            'captcha.sitekey' => 'env_sitekey',
        ]);

        $this->bootProvider();

        // Nothing to hydrate -> the existing (env/config-file) values remain.
        $this->assertSame('env_secret', config('captcha.secret'));
        $this->assertSame('env_sitekey', config('captcha.sitekey'));
    }
}
