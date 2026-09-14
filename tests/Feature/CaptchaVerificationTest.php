<?php

namespace Tests\Feature;

use App\Services\Captcha\CapDriver;
use App\Services\Captcha\HCaptchaDriver;
use App\Services\Captcha\TurnstileDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaptchaVerificationTest extends TestCase
{
    // ---------------------------------------------------------------------
    // captcha_verify validation rule (driver-agnostic)
    // ---------------------------------------------------------------------

    #[Test]
    public function captcha_verify_rule_fails_when_token_is_missing(): void
    {
        config(['captcha.driver' => 'turnstile']);
        $this->app->forgetInstance('captcha.manager');

        Http::fake(); // no verify request should be made for a missing token

        // Mirrors real controller usage: required|captcha_verify.
        $validator = Validator::make(
            [],
            ['cf-turnstile-response' => 'required|captcha_verify']
        );

        $this->assertTrue($validator->fails());
        Http::assertNothingSent();
    }

    #[Test]
    public function captcha_verify_rule_passes_when_provider_confirms(): void
    {
        config([
            'captcha.driver' => 'turnstile',
            'captcha.turnstile.secret' => 'sekret',
        ]);
        $this->app->forgetInstance('captcha.manager');

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $validator = Validator::make(
            ['cf-turnstile-response' => 'a-token'],
            ['cf-turnstile-response' => 'captcha_verify']
        );

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function captcha_verify_rule_fails_when_provider_rejects(): void
    {
        config([
            'captcha.driver' => 'turnstile',
            'captcha.turnstile.secret' => 'sekret',
        ]);
        $this->app->forgetInstance('captcha.manager');

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
        ]);

        $validator = Validator::make(
            ['cf-turnstile-response' => 'bad-token'],
            ['cf-turnstile-response' => 'captcha_verify']
        );

        $this->assertTrue($validator->fails());
    }

    #[Test]
    public function captcha_verify_rule_uses_the_active_driver_field(): void
    {
        // With the cap driver active, the rule should validate against the
        // cap-token field pulled from the request, not the attribute name.
        config([
            'captcha.driver' => 'cap',
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
        ]);
        $this->app->forgetInstance('captcha.manager');

        Http::fake([
            'cap.example.com/*' => Http::response(['success' => true], 200),
        ]);

        $validator = Validator::make(
            ['cap-token' => 'tok'],
            ['cap-token' => 'required|captcha_verify']
        );

        $this->assertTrue($validator->passes());
    }

    // ---------------------------------------------------------------------
    // Turnstile driver verify()
    // ---------------------------------------------------------------------

    #[Test]
    public function turnstile_verify_returns_false_for_empty_token_without_calling_out(): void
    {
        config(['captcha.turnstile.secret' => 'sekret']);
        Http::fake();

        $this->assertFalse((new TurnstileDriver)->verify([]));
        $this->assertFalse((new TurnstileDriver)->verify(['cf-turnstile-response' => '']));

        Http::assertNothingSent();
    }

    #[Test]
    public function turnstile_verify_true_on_success_response(): void
    {
        config(['captcha.turnstile.secret' => 'sekret']);
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->assertTrue((new TurnstileDriver)->verify(['cf-turnstile-response' => 'tok']));
    }

    #[Test]
    public function turnstile_verify_false_on_unsuccessful_response(): void
    {
        config(['captcha.turnstile.secret' => 'sekret']);
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['bad']], 200),
        ]);

        $this->assertFalse((new TurnstileDriver)->verify(['cf-turnstile-response' => 'tok']));
    }

    #[Test]
    public function turnstile_fail_open_lets_requests_through_on_network_error(): void
    {
        config([
            'captcha.turnstile.secret' => 'sekret',
            'captcha.turnstile.fail_open' => true,
        ]);
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response('boom', 500),
        ]);

        $this->assertTrue((new TurnstileDriver)->verify(['cf-turnstile-response' => 'anything']));
    }

    #[Test]
    public function turnstile_fail_closed_blocks_requests_on_network_error(): void
    {
        config([
            'captcha.turnstile.secret' => 'sekret',
            'captcha.turnstile.fail_open' => false,
        ]);
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response('boom', 500),
        ]);

        $this->assertFalse((new TurnstileDriver)->verify(['cf-turnstile-response' => 'anything']));
    }

    #[Test]
    public function turnstile_sends_secret_and_response(): void
    {
        config(['captcha.turnstile.secret' => 'my-secret']);
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        (new TurnstileDriver)->verify(['cf-turnstile-response' => 'my-token']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
                && $request['secret'] === 'my-secret'
                && $request['response'] === 'my-token';
        });
    }

    // ---------------------------------------------------------------------
    // Cap driver verify() — hits {endpoint}/{sitekey}/siteverify
    // ---------------------------------------------------------------------

    #[Test]
    public function cap_verify_returns_false_for_empty_token(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
        ]);
        Http::fake();

        $this->assertFalse((new CapDriver)->verify([]));
        Http::assertNothingSent();
    }

    #[Test]
    public function cap_verify_returns_false_when_not_configured(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => null, // missing -> apiEndpoint() empty
            'captcha.cap.secret' => 'sk',
        ]);
        Http::fake();

        $this->assertFalse((new CapDriver)->verify(['cap-token' => 'tok']));
        Http::assertNothingSent();
    }

    #[Test]
    public function cap_verify_posts_to_composed_siteverify_url(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => '3c87a0e810',
            'captcha.cap.secret' => 'sk-secret',
        ]);
        Http::fake([
            'cap.example.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->assertTrue((new CapDriver)->verify(['cap-token' => 'the-token']));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://cap.example.com/3c87a0e810/siteverify';
        });
    }

    #[Test]
    public function cap_verify_false_when_server_rejects(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
        ]);
        Http::fake([
            'cap.example.com/*' => Http::response(['success' => false], 200),
        ]);

        $this->assertFalse((new CapDriver)->verify(['cap-token' => 'tok']));
    }

    #[Test]
    public function cap_verify_uses_custom_token_field(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
            'captcha.cap.token_field' => 'my-token',
        ]);
        Http::fake([
            'cap.example.com/*' => Http::response(['success' => true], 200),
        ]);

        $driver = new CapDriver;

        // The custom field carries the token; the default name is ignored.
        $this->assertTrue($driver->verify(['my-token' => 'tok']));
        $this->assertFalse($driver->verify(['cap-token' => 'tok']));
    }

    // ---------------------------------------------------------------------
    // Cap driver verify() — inlined HTTP behavior (no oliweb/laravel-cap pkg)
    // ---------------------------------------------------------------------

    #[Test]
    public function cap_verify_sends_json_secret_and_response(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'my-cap-secret',
        ]);
        Http::fake([
            'cap.example.com/*' => Http::response(['success' => true], 200),
        ]);

        (new CapDriver)->verify(['cap-token' => 'my-token']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://cap.example.com/abc/siteverify'
                && $request->hasHeader('Content-Type', 'application/json')
                && $request['secret'] === 'my-cap-secret'
                && $request['response'] === 'my-token';
        });
    }

    #[Test]
    public function cap_verify_defaults_to_false_when_success_key_is_absent(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
        ]);
        Http::fake([
            'cap.example.com/*' => Http::response(['foo' => 'bar'], 200),
        ]);

        $this->assertFalse((new CapDriver)->verify(['cap-token' => 'tok']));
    }

    #[Test]
    public function cap_fail_open_lets_requests_through_on_http_error(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
            'captcha.cap.fail_open' => true,
        ]);
        Http::fake([
            'cap.example.com/*' => Http::response('server error', 500),
        ]);

        $this->assertTrue((new CapDriver)->verify(['cap-token' => 'tok']));
    }

    #[Test]
    public function cap_fail_closed_blocks_requests_on_http_error(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
            'captcha.cap.fail_open' => false,
        ]);
        Http::fake([
            'cap.example.com/*' => Http::response('server error', 500),
        ]);

        $this->assertFalse((new CapDriver)->verify(['cap-token' => 'tok']));
    }

    #[Test]
    public function cap_fail_open_lets_requests_through_on_network_exception(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
            'captcha.cap.fail_open' => true,
        ]);
        Http::fake(function () {
            throw new ConnectionException('connection refused');
        });

        $this->assertTrue((new CapDriver)->verify(['cap-token' => 'tok']));
    }

    #[Test]
    public function cap_fail_closed_blocks_requests_on_network_exception(): void
    {
        config([
            'captcha.cap.endpoint' => 'https://cap.example.com',
            'captcha.cap.sitekey' => 'abc',
            'captcha.cap.secret' => 'sk',
            'captcha.cap.fail_open' => false,
        ]);
        Http::fake(function () {
            throw new ConnectionException('connection refused');
        });

        $this->assertFalse((new CapDriver)->verify(['cap-token' => 'tok']));
    }

    // ---------------------------------------------------------------------
    // hCaptcha driver verify() — inlined HTTP behavior (no buzz package)
    // ---------------------------------------------------------------------

    #[Test]
    public function hcaptcha_verify_returns_false_for_empty_token_without_calling_out(): void
    {
        config(['captcha.hcaptcha.secret' => 'sekret']);
        Http::fake();

        $this->assertFalse((new HCaptchaDriver)->verify([]));
        $this->assertFalse((new HCaptchaDriver)->verify(['h-captcha-response' => '']));

        Http::assertNothingSent();
    }

    #[Test]
    public function hcaptcha_verify_true_on_success_response(): void
    {
        config(['captcha.hcaptcha.secret' => 'sekret']);
        Http::fake([
            'api.hcaptcha.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->assertTrue((new HCaptchaDriver)->verify(['h-captcha-response' => 'tok']));
    }

    #[Test]
    public function hcaptcha_verify_false_on_unsuccessful_response(): void
    {
        config(['captcha.hcaptcha.secret' => 'sekret']);
        Http::fake([
            'api.hcaptcha.com/*' => Http::response(['success' => false], 200),
        ]);

        $this->assertFalse((new HCaptchaDriver)->verify(['h-captcha-response' => 'tok']));
    }

    #[Test]
    public function hcaptcha_verify_defaults_to_false_when_success_key_is_absent(): void
    {
        config(['captcha.hcaptcha.secret' => 'sekret']);
        Http::fake([
            'api.hcaptcha.com/*' => Http::response(['foo' => 'bar'], 200),
        ]);

        $this->assertFalse((new HCaptchaDriver)->verify(['h-captcha-response' => 'tok']));
    }

    #[Test]
    public function hcaptcha_sends_form_secret_and_response(): void
    {
        config(['captcha.hcaptcha.secret' => 'my-hcaptcha-secret']);
        Http::fake([
            'api.hcaptcha.com/*' => Http::response(['success' => true], 200),
        ]);

        (new HCaptchaDriver)->verify(['h-captcha-response' => 'my-token']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.hcaptcha.com/siteverify'
                && $request['secret'] === 'my-hcaptcha-secret'
                && $request['response'] === 'my-token';
        });
    }

    #[Test]
    public function hcaptcha_fail_open_lets_requests_through_on_http_error(): void
    {
        config([
            'captcha.hcaptcha.secret' => 'sekret',
            'captcha.hcaptcha.fail_open' => true,
        ]);
        Http::fake([
            'api.hcaptcha.com/*' => Http::response('server error', 500),
        ]);

        $this->assertTrue((new HCaptchaDriver)->verify(['h-captcha-response' => 'tok']));
    }

    #[Test]
    public function hcaptcha_fail_closed_blocks_requests_on_http_error(): void
    {
        config([
            'captcha.hcaptcha.secret' => 'sekret',
            'captcha.hcaptcha.fail_open' => false,
        ]);
        Http::fake([
            'api.hcaptcha.com/*' => Http::response('server error', 500),
        ]);

        $this->assertFalse((new HCaptchaDriver)->verify(['h-captcha-response' => 'tok']));
    }

    #[Test]
    public function hcaptcha_fail_open_lets_requests_through_on_network_exception(): void
    {
        config([
            'captcha.hcaptcha.secret' => 'sekret',
            'captcha.hcaptcha.fail_open' => true,
        ]);
        Http::fake(function () {
            throw new ConnectionException('connection refused');
        });

        $this->assertTrue((new HCaptchaDriver)->verify(['h-captcha-response' => 'tok']));
    }
}
