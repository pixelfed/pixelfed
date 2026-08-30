<?php

namespace Tests\Unit;

use App\Services\WebPushService;
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
}
