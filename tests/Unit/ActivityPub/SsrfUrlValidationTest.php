<?php

namespace Tests\Unit\ActivityPub;

use App\Services\SecureMediaFetchService;
use App\Util\ActivityPub\Helpers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Regression tests for the SSRF hardening in the remote media/avatar fetch
 * path (variant of CVE-2026-71246).
 *
 * These cover the deterministic, network-free guards:
 *   - isPublicIp() rejects private/reserved/link-local ranges (incl. the
 *     169.254.169.254 cloud-metadata address).
 *   - normalizeHost() rejects IP literals and localhost domains.
 *   - validateUrl() rejects non-https, userinfo, control chars, backslashes
 *     and IP-literal URLs.
 *   - SecureMediaFetchService fails closed (no network call) for URLs that
 *     cannot pass validation.
 */
class SsrfUrlValidationTest extends TestCase
{
    // ---- isPublicIp -------------------------------------------------------

    public static function privateAndReservedIps(): array
    {
        return [
            'aws/gcp metadata' => ['169.254.169.254'],
            'link-local' => ['169.254.0.1'],
            'loopback v4' => ['127.0.0.1'],
            'rfc1918 10/8' => ['10.0.0.1'],
            'rfc1918 172.16/12' => ['172.18.0.1'],
            'rfc1918 192.168/16' => ['192.168.1.1'],
            'loopback v6' => ['::1'],
            'unique local v6' => ['fd00::1'],
            'unspecified' => ['0.0.0.0'],
        ];
    }

    #[Test]
    #[DataProvider('privateAndReservedIps')]
    public function it_rejects_private_and_reserved_ips(string $ip): void
    {
        $this->assertFalse(Helpers::isPublicIp($ip), $ip.' should be treated as non-public');
    }

    public static function publicIps(): array
    {
        return [
            'cloudflare dns' => ['1.1.1.1'],
            'google dns' => ['8.8.8.8'],
            'public v6' => ['2606:4700:4700::1111'],
        ];
    }

    #[Test]
    #[DataProvider('publicIps')]
    public function it_accepts_public_ips(string $ip): void
    {
        $this->assertTrue(Helpers::isPublicIp($ip), $ip.' should be public');
    }

    // ---- normalizeHost ----------------------------------------------------

    #[Test]
    public function it_rejects_ip_literal_hosts(): void
    {
        $this->assertNull(Helpers::normalizeHost('169.254.169.254'));
        $this->assertNull(Helpers::normalizeHost('127.0.0.1'));
        $this->assertNull(Helpers::normalizeHost('::1'));
    }

    #[Test]
    public function it_rejects_localhost_domains(): void
    {
        $this->assertNull(Helpers::normalizeHost('localhost'));
    }

    #[Test]
    public function it_normalizes_regular_hosts(): void
    {
        $this->assertSame('example.com', Helpers::normalizeHost('Example.com.'));
    }

    // ---- validateUrl ------------------------------------------------------

    public static function invalidUrls(): array
    {
        return [
            'http scheme' => ['http://example.com/avatar.jpg'],
            'ftp scheme' => ['ftp://example.com/avatar.jpg'],
            'ip literal https' => ['https://169.254.169.254/latest/meta-data/'],
            'private ip literal' => ['https://172.18.0.1:9000/internal.jpg'],
            'userinfo smuggling' => ['https://user:pass@example.com/a.jpg'],
            'userinfo at-trick' => ['https://example.com@169.254.169.254/a.jpg'],
            'control char' => ["https://example.com/\r\n/a.jpg"],
            'backslash' => ['https://example.com\\@evil.com/a.jpg'],
            'no dot host' => ['https://localhost/a.jpg'],
            'empty' => [''],
        ];
    }

    #[Test]
    #[DataProvider('invalidUrls')]
    public function it_rejects_unsafe_urls(string $url): void
    {
        $this->assertFalse(Helpers::validateUrl($url), $url.' should be rejected');
    }

    #[Test]
    public function it_accepts_a_normal_https_url_in_non_prod(): void
    {
        // In the local/testing environment shouldCheckDNS()/shouldCheckBans()
        // are off, so a well-formed public https URL normalizes successfully.
        $url = 'https://example.com/avatar.jpg';
        $this->assertSame($url, Helpers::validateUrl($url));
    }

    // ---- SecureMediaFetchService fails closed -----------------------------

    #[Test]
    public function secure_fetch_head_fails_closed_on_invalid_url(): void
    {
        // These never resolve/connect because validateUrl rejects them first.
        $this->assertFalse(SecureMediaFetchService::head('http://169.254.169.254/'));
        $this->assertFalse(SecureMediaFetchService::head('https://172.18.0.1:9000/internal.jpg'));
        $this->assertFalse(SecureMediaFetchService::head('https://example.com@169.254.169.254/a.jpg'));
    }

    #[Test]
    public function secure_fetch_get_fails_closed_on_invalid_url(): void
    {
        $this->assertFalse(SecureMediaFetchService::get('http://169.254.169.254/'));
        $this->assertFalse(SecureMediaFetchService::get('https://172.18.0.1:9000/internal.jpg'));
    }
}
