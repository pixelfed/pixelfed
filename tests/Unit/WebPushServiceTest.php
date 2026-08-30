<?php

namespace Tests\Unit;

use App\Models\OAuthToken;
use App\Services\WebPushService;
use Laravel\Passport\TransientToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebPushServiceTest extends TestCase
{
    #[Test]
    public function it_is_disabled_without_vapid_keys(): void
    {
        config(['webpush.vapid' => [
            'subject' => null,
            'public_key' => null,
            'private_key' => null,
            'pem_file' => null,
        ]]);

        $this->assertFalse(WebPushService::enabled());
    }

    #[Test]
    public function it_is_disabled_with_only_half_a_keypair(): void
    {
        config(['webpush.vapid' => [
            'subject' => 'mailto:admin@example.org',
            'public_key' => 'a-public-key',
            'private_key' => null,
            'pem_file' => null,
        ]]);

        $this->assertFalse(WebPushService::enabled());
    }

    #[Test]
    public function it_is_enabled_with_a_keypair(): void
    {
        config(['webpush.vapid' => [
            'subject' => 'mailto:admin@example.org',
            'public_key' => 'a-public-key',
            'private_key' => 'a-private-key',
            'pem_file' => null,
        ]]);

        $this->assertTrue(WebPushService::enabled());
    }

    #[Test]
    public function it_is_enabled_with_a_pem_file(): void
    {
        config(['webpush.vapid' => [
            'subject' => 'mailto:admin@example.org',
            'public_key' => null,
            'private_key' => null,
            'pem_file' => '/etc/pixelfed/vapid.pem',
        ]]);

        $this->assertTrue(WebPushService::enabled());
    }

    #[Test]
    public function it_identifies_a_bearer_token_by_its_own_id(): void
    {
        $token = new OAuthToken;
        $token->id = 'f1e2d3c4b5a6';

        $this->assertSame('f1e2d3c4b5a6', WebPushService::clientIdentifier($token, null));
    }

    /**
     * The regression this guards: TransientToken has no `id`, so reading one
     * off it yielded null and filed every browser subscription under NULL.
     */
    #[Test]
    public function it_identifies_a_session_authenticated_browser_without_reading_a_missing_id(): void
    {
        $identifier = WebPushService::clientIdentifier(new TransientToken, 'a-session-id');

        $this->assertNotNull($identifier);
        $this->assertStringStartsWith('session:', $identifier);

        // The session id itself is a live credential and must not be stored.
        $this->assertStringNotContainsString('a-session-id', $identifier);
    }

    #[Test]
    public function it_gives_two_browsers_distinct_identifiers(): void
    {
        $first = WebPushService::clientIdentifier(new TransientToken, 'session-one');
        $second = WebPushService::clientIdentifier(new TransientToken, 'session-two');

        $this->assertNotSame($first, $second);
    }

    #[Test]
    public function it_is_stable_for_the_same_session(): void
    {
        $this->assertSame(
            WebPushService::clientIdentifier(new TransientToken, 'session-one'),
            WebPushService::clientIdentifier(new TransientToken, 'session-one'),
        );
    }

    #[Test]
    public function it_has_no_identifier_for_a_transient_token_without_a_session(): void
    {
        $this->assertNull(WebPushService::clientIdentifier(new TransientToken, null));
    }

    #[Test]
    public function it_produces_an_identifier_that_fits_the_column(): void
    {
        $identifier = WebPushService::clientIdentifier(new TransientToken, str_repeat('a', 512));

        // access_token_id is varchar(100).
        $this->assertLessThanOrEqual(100, strlen($identifier));
    }
}
