<?php

namespace App\Services;

/**
 * Decides whether a Web Push endpoint is safe for this server to POST to.
 *
 * A push endpoint is a URL supplied by an authenticated user, which the queue
 * worker later makes an outbound request to from inside the instance's
 * network. On an instance with open registration, anyone can obtain a token
 * with the `push` scope and register one, so this is a classic SSRF surface.
 *
 * Validating the URL when it is registered is necessary but not sufficient:
 * the host is resolved again when the request is actually made, so a hostile
 * nameserver can answer with a public address at registration and a private
 * one at send time (DNS rebinding).
 *
 * inspect() therefore returns the addresses it validated in CURLOPT_RESOLVE
 * form, so the sender can pin the connection to exactly those and never let
 * curl perform its own lookup. An endpoint that cannot be pinned is not sent
 * to at all — failing closed, because falling back to an unpinned request is
 * precisely the case this exists to prevent.
 */
class WebPushEndpointGuard
{
    /**
     * @return array{ok: bool, reason: ?string, resolve: ?string}
     */
    public static function inspect(string $url): array
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return self::fail('not a valid absolute URL');
        }

        // https only: rules out file://, gopher:// and friends, and takes most
        // cloud metadata services off the table since they are plain HTTP.
        if (strtolower($parts['scheme']) !== 'https') {
            return self::fail('must use https');
        }

        if (! empty($parts['user']) || ! empty($parts['pass'])) {
            return self::fail('must not contain embedded credentials');
        }

        // trim() strips the brackets around an IPv6 literal host.
        $host = trim($parts['host'], '[]');
        $port = $parts['port'] ?? 443;

        // A literal address needs no lookup, so there is nothing to rebind and
        // nothing to pin — it just has to be public.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicAddress($host)
                ? ['ok' => true, 'reason' => null, 'resolve' => null]
                : self::fail('resolves to a private or reserved address');
        }

        $addresses = self::resolve($host);

        if (empty($addresses)) {
            return self::fail('host could not be resolved');
        }

        $safe = array_values(array_filter($addresses, [self::class, 'isPublicAddress']));

        // Any private answer at all is treated as disqualifying rather than
        // filtered out: a host that resolves to a mix of public and internal
        // addresses has no legitimate reason to be a push endpoint.
        if (count($safe) !== count($addresses)) {
            return self::fail('resolves to a private or reserved address');
        }

        $formatted = array_map(
            fn ($address) => str_contains($address, ':') ? '['.$address.']' : $address,
            $safe
        );

        return [
            'ok' => true,
            'reason' => null,
            'resolve' => $host.':'.$port.':'.implode(',', $formatted),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function resolve(string $host): array
    {
        $addresses = gethostbynamel($host) ?: [];

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (! empty($record['ipv6'])) {
                $addresses[] = $record['ipv6'];
            }
        }

        return $addresses;
    }

    public static function isPublicAddress(string $address): bool
    {
        // NO_PRIV_RANGE covers RFC1918 and fc00::/7 + fe80::/10; NO_RES_RANGE
        // covers loopback, link-local (including 169.254.169.254) and the
        // other reserved blocks.
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * @return array{ok: bool, reason: ?string, resolve: ?string}
     */
    private static function fail(string $reason): array
    {
        return ['ok' => false, 'reason' => $reason, 'resolve' => null];
    }
}
