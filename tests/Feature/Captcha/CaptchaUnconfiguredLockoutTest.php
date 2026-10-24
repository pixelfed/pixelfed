<?php

namespace Tests\Feature\Captcha;

use App\Models\User;
use App\Services\Captcha\CaptchaManager;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Captcha must not be enforced while the active driver is missing its
 * credentials. Enforcing it would render a widget that can never succeed and
 * lock every user out of the auth surfaces with a captcha 422.
 */
class CaptchaUnconfiguredLockoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const FIELD = 'cf-turnstile-response';

    private function unconfigured(): void
    {
        config([
            'captcha.enabled' => true,
            'captcha.driver' => 'turnstile',
            'captcha.turnstile.secret' => '',
            'captcha.turnstile.sitekey' => '',
            'captcha.active.login' => true,
        ]);
    }

    private function configured(): void
    {
        config([
            'captcha.enabled' => true,
            'captcha.driver' => 'turnstile',
            'captcha.turnstile.secret' => 'test-secret',
            'captcha.turnstile.sitekey' => 'test-sitekey',
            'captcha.active.login' => true,
        ]);
    }

    #[Test]
    public function active_on_returns_false_when_driver_unconfigured(): void
    {
        $this->unconfigured();

        $this->assertFalse((new CaptchaManager($this->app))->activeOn('login'));
    }

    #[Test]
    public function active_on_returns_true_when_driver_configured(): void
    {
        $this->configured();

        $manager = new CaptchaManager($this->app);

        $this->assertTrue($manager->activeOn('login'));
        $this->assertStringContainsString('data-sitekey="test-sitekey"', $manager->active()->render());
    }

    #[Test]
    public function login_is_not_captcha_locked_when_driver_unconfigured(): void
    {
        $this->unconfigured();
        $this->app->forgetInstance('captcha.manager');
        Http::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertJsonMissingValidationErrors([self::FIELD]);
        Http::assertNothingSent();
    }

    #[Test]
    public function login_enforces_captcha_when_driver_configured(): void
    {
        $this->configured();
        $this->app->forgetInstance('captcha.manager');
        $user = User::factory()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([self::FIELD]);
    }
}
