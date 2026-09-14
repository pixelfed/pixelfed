<?php

namespace Tests\Feature;

use App\Services\Captcha\TurnstileDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaptchaVerificationTest extends TestCase
{
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
    public function turnstile_fail_open_lets_requests_through_on_network_error(): void
    {
        config([
            'captcha.turnstile.secret' => 'sekret',
            'captcha.turnstile.fail_open' => true,
        ]);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response('boom', 500),
        ]);

        $driver = new TurnstileDriver;

        $this->assertTrue($driver->verify(['cf-turnstile-response' => 'anything']));
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

        $driver = new TurnstileDriver;

        $this->assertFalse($driver->verify(['cf-turnstile-response' => 'anything']));
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
}
