<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Honeypot\ProtectAgainstSpam;
use Tests\TestCase;

/**
 * HTTP-level tests that the captcha_verify rule is actually enforced on each
 * auth flow when its surface is enabled, driven through the real routes and
 * controllers. Uses the turnstile driver with a faked siteverify endpoint.
 */
class CaptchaEnforcementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const FIELD = 'cf-turnstile-response';

    protected function setUp(): void
    {
        parent::setUp();

        // Use turnstile so we can fake the verification HTTP call.
        config([
            'captcha.enabled' => true,
            'captcha.driver' => 'turnstile',
            'captcha.turnstile.secret' => 'test-secret',
            'captcha.turnstile.sitekey' => 'test-sitekey',
        ]);
        $this->app->forgetInstance('captcha.manager');
    }

    private function fakeCaptchaSuccess(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);
    }

    private function fakeCaptchaFailure(): void
    {
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
        ]);
    }

    // ---------------------------------------------------------------------
    // Login — surface: login
    // ---------------------------------------------------------------------

    #[Test]
    public function login_is_blocked_when_captcha_token_is_missing(): void
    {
        config(['captcha.active.login' => true]);
        $user = User::factory()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([self::FIELD]);
    }

    #[Test]
    public function login_is_blocked_when_captcha_token_is_invalid(): void
    {
        config(['captcha.active.login' => true]);
        $this->fakeCaptchaFailure();
        $user = User::factory()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
            self::FIELD => 'bad-token',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([self::FIELD]);
    }

    #[Test]
    public function login_passes_captcha_with_a_valid_token(): void
    {
        config(['captcha.active.login' => true]);
        $this->fakeCaptchaSuccess();
        $user = User::factory()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
            self::FIELD => 'good-token',
        ]);

        // Captcha cleared -> no captcha error (auth may still redirect/succeed).
        $response->assertJsonMissingValidationErrors([self::FIELD]);
    }

    #[Test]
    public function login_ignores_captcha_when_surface_disabled(): void
    {
        config(['captcha.active.login' => false]);
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
    public function login_ignores_captcha_when_globally_disabled(): void
    {
        config(['captcha.enabled' => false, 'captcha.active.login' => true]);
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

    // ---------------------------------------------------------------------
    // Registration — surface: register
    // ---------------------------------------------------------------------

    #[Test]
    public function registration_is_blocked_when_captcha_token_is_missing(): void
    {
        config([
            'captcha.active.register' => true,
            'pixelfed.open_registration' => true,
        ]);
        $this->withoutMiddleware(ProtectAgainstSpam::class);

        $response = $this->postJson('/register', [
            'agecheck' => '1',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([self::FIELD]);
    }

    #[Test]
    public function registration_captcha_passes_with_a_valid_token(): void
    {
        config([
            'captcha.active.register' => true,
            'pixelfed.open_registration' => true,
        ]);
        $this->fakeCaptchaSuccess();
        $this->withoutMiddleware(ProtectAgainstSpam::class);

        $response = $this->postJson('/register', [
            'agecheck' => '1',
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            self::FIELD => 'good-token',
        ]);

        // Whatever else validation says, the captcha field must have cleared.
        $response->assertJsonMissingValidationErrors([self::FIELD]);
    }

    // ---------------------------------------------------------------------
    // Forgot password (send reset link) — surface: forgot_password
    // ---------------------------------------------------------------------

    #[Test]
    public function forgot_password_is_blocked_when_captcha_token_is_missing(): void
    {
        config(['captcha.active.forgot_password' => true]);

        $response = $this->postJson('/password/email', [
            'email' => 'someone@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([self::FIELD]);
    }

    #[Test]
    public function forgot_password_passes_captcha_with_a_valid_token(): void
    {
        config(['captcha.active.forgot_password' => true]);
        $this->fakeCaptchaSuccess();

        $response = $this->postJson('/password/email', [
            'email' => 'someone@example.com',
            self::FIELD => 'good-token',
        ]);

        $response->assertJsonMissingValidationErrors([self::FIELD]);
    }

    // ---------------------------------------------------------------------
    // Reset password — surface: password_reset
    // ---------------------------------------------------------------------

    #[Test]
    public function reset_password_is_blocked_when_captcha_token_is_missing(): void
    {
        config(['captcha.active.password_reset' => true]);

        $response = $this->postJson('/password/reset', [
            'token' => 'sometoken',
            'email' => 'someone@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([self::FIELD]);
    }

    // ---------------------------------------------------------------------
    // Forgot email (username recovery) — surface: forgot_email
    // ---------------------------------------------------------------------

    #[Test]
    public function forgot_email_is_blocked_when_captcha_token_is_missing(): void
    {
        config([
            'captcha.active.forgot_email' => true,
            'security.forgot-email.enabled' => true,
        ]);
        // The route is rate-limited (throttle:10,900); bypass it so repeated
        // test runs don't hit a 429 instead of the captcha validation error.
        $this->withoutMiddleware(ThrottleRequests::class);
        $user = User::factory()->create();

        $response = $this->postJson('/auth/forgot/email', [
            'username' => $user->username,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([self::FIELD]);
    }

    #[Test]
    public function forgot_email_passes_captcha_with_a_valid_token(): void
    {
        config([
            'captcha.active.forgot_email' => true,
            'security.forgot-email.enabled' => true,
        ]);
        $this->fakeCaptchaSuccess();
        Mail::fake();
        $this->withoutMiddleware(ThrottleRequests::class);
        $user = User::factory()->create();

        // Success path redirects (not JSON); assert the captcha cleared, i.e. the
        // session has no error on the captcha field.
        $response = $this->post('/auth/forgot/email', [
            'username' => $user->username,
            self::FIELD => 'good-token',
        ]);

        $response->assertSessionHasNoErrors();
    }
}
