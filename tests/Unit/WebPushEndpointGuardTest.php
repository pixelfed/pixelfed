<?php

namespace Tests\Unit;

use App\Services\WebPushEndpointGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The guard decides whether the queue worker may make an outbound request to
 * a user-supplied URL, so these cases are the SSRF boundary rather than
 * cosmetic validation.
 *
 * Every case here uses a literal address or a malformed URL, so no test
 * performs a DNS lookup.
 */
class WebPushEndpointGuardTest extends TestCase
{
    public static function rejectedEndpoints(): array
    {
        return [
            'plain http' => ['http://93.184.216.34/wp/abc'],
            'not a url' => ['not-a-url'],
            'empty' => [''],
            'no scheme' => ['//93.184.216.34/wp/abc'],
            'file scheme' => ['file:///etc/passwd'],
            'gopher scheme' => ['gopher://93.184.216.34/'],
            'embedded credentials' => ['https://user:pass@93.184.216.34/wp/abc'],
            'loopback' => ['https://127.0.0.1/wp/abc'],
            'ipv6 loopback' => ['https://[::1]/wp/abc'],
            'rfc1918 ten' => ['https://10.0.0.5/wp/abc'],
            'rfc1918 192' => ['https://192.168.1.1/wp/abc'],
            'rfc1918 172' => ['https://172.16.0.1/wp/abc'],
            'cloud metadata' => ['https://169.254.169.254/latest/meta-data/'],
            'ipv6 unique local' => ['https://[fd00::1]/wp/abc'],
        ];
    }

    #[Test]
    #[DataProvider('rejectedEndpoints')]
    public function it_rejects_unsafe_endpoints(string $url): void
    {
        $result = WebPushEndpointGuard::inspect($url);

        $this->assertFalse($result['ok'], "Expected {$url} to be rejected");
        $this->assertNotNull($result['reason']);
        $this->assertNull($result['resolve']);
    }

    #[Test]
    public function it_accepts_a_public_literal_address(): void
    {
        $result = WebPushEndpointGuard::inspect('https://93.184.216.34/wp/abc');

        $this->assertTrue($result['ok']);
        $this->assertNull($result['reason']);

        // A literal address needs no lookup, so there is nothing to pin.
        $this->assertNull($result['resolve']);
    }

    #[Test]
    public function it_accepts_a_public_literal_ipv6_address(): void
    {
        $result = WebPushEndpointGuard::inspect('https://[2606:2800:220:1:248:1893:25c8:1946]/wp/abc');

        $this->assertTrue($result['ok']);
    }

    #[Test]
    public function it_classifies_addresses(): void
    {
        $this->assertTrue(WebPushEndpointGuard::isPublicAddress('93.184.216.34'));
        $this->assertTrue(WebPushEndpointGuard::isPublicAddress('2606:2800:220:1:248:1893:25c8:1946'));

        $this->assertFalse(WebPushEndpointGuard::isPublicAddress('127.0.0.1'));
        $this->assertFalse(WebPushEndpointGuard::isPublicAddress('10.0.0.1'));
        $this->assertFalse(WebPushEndpointGuard::isPublicAddress('169.254.169.254'));
        $this->assertFalse(WebPushEndpointGuard::isPublicAddress('::1'));
        $this->assertFalse(WebPushEndpointGuard::isPublicAddress('fd00::1'));
        $this->assertFalse(WebPushEndpointGuard::isPublicAddress('definitely not an address'));
    }
}
