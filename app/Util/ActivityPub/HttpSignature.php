<?php

namespace App\Util\ActivityPub;

use App\Models\InstanceActor;
use App\Models\Profile;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Stringable;
use Throwable;

class HttpSignature
{
    /*
     * Originally based on:
     * https://github.com/aaronpk/Nautilus/blob/master/app/ActivityPub/HTTPSignature.php
     *
     * Legacy HTTP Signatures / draft-cavage implementation used for
     * ActivityPub interoperability.
     */

    private const SIGNATURE_ALGORITHM = 'rsa-sha256';

    private const OPENSSL_ALGORITHM = OPENSSL_ALGO_SHA256;

    private const SUPPORTED_METHODS = [
        'get',
        'post',
    ];

    /**
     * Sign a request using a local Pixelfed profile.
     *
     * Returns headers in cURL format:
     *
     * [
     *     "Host: example.org",
     *     "Date: ...",
     *     "Digest: SHA-256=...",
     *     "Signature: ...",
     * ]
     *
     * @param  array<string, mixed>  $addlHeaders
     * @return array<int, string>
     *
     * @throws JsonException
     */
    public static function sign(
        Profile $profile,
        string $url,
        mixed $body = false,
        array $addlHeaders = []
    ): array {
        if (empty($profile->private_key)) {
            return [];
        }

        try {
            $headers = self::signRequest(
                privateKey: $profile->private_key,
                keyId: $profile->keyId(),
                url: $url,
                body: $body,
                addlHeaders: $addlHeaders,
                method: 'post'
            );
        } catch (Throwable) {
            return [];
        }

        return self::_headersToCurlArray($headers);
    }

    /**
     * Sign using a pre-computed SHA-256 digest.
     *
     * $digest must be the base64 digest value WITHOUT the "SHA-256=" prefix.
     *
     * Returns an associative header array.
     *
     * @param  array<string, mixed>  $addlHeaders
     * @return array<string, string>
     */
    public static function signRawWithDigest(
        string $privateKey,
        string $keyId,
        string $url,
        string $digest,
        array $addlHeaders = [],
        string $method = 'post'
    ): array {
        if ($privateKey === '') {
            throw new RuntimeException(
                'Private key is missing.'
            );
        }

        if ($keyId === '') {
            throw new RuntimeException(
                'Key ID is missing.'
            );
        }

        if ($digest === '') {
            throw new RuntimeException(
                'Digest is missing.'
            );
        }

        return self::signRequest(
            privateKey: $privateKey,
            keyId: $keyId,
            url: $url,
            body: false,
            addlHeaders: $addlHeaders,
            method: $method,
            digest: $digest
        );
    }

    /**
     * Sign using an explicitly supplied private key and key ID.
     *
     * Returns headers in cURL format for backwards compatibility.
     *
     * @param  array<string, mixed>  $addlHeaders
     * @return array<int, string>
     *
     * @throws JsonException
     */
    public static function signRaw(
        ?string $privateKey,
        ?string $keyId,
        string $url,
        mixed $body = false,
        array $addlHeaders = []
    ): array {
        if (empty($privateKey) || empty($keyId)) {
            return [];
        }

        try {
            $headers = self::signRequest(
                privateKey: $privateKey,
                keyId: $keyId,
                url: $url,
                body: $body,
                addlHeaders: $addlHeaders,
                method: 'post'
            );
        } catch (Throwable) {
            return [];
        }

        return self::_headersToCurlArray($headers);
    }

    /**
     * Sign a request as the Pixelfed instance actor.
     *
     * Returns an associative header array.
     *
     * @param  array<string, mixed>  $addlHeaders
     * @return array<string, string>
     *
     * @throws JsonException
     */
    public static function instanceActorSign(
        string $url,
        mixed $body = false,
        array $addlHeaders = [],
        string $method = 'post'
    ): array {
        return self::signRequest(
            privateKey: self::instanceActorPrivateKey(),
            keyId: self::instanceActorKeyId(),
            url: $url,
            body: $body,
            addlHeaders: $addlHeaders,
            method: $method
        );
    }

    /**
     * Sign as the instance actor using a pre-computed digest.
     *
     * $digest must be the base64 digest value WITHOUT the "SHA-256=" prefix.
     *
     * @param  array<string, mixed>  $addlHeaders
     * @return array<string, string>
     */
    public static function instanceActorSignWithDigest(
        string $url,
        string $digest,
        array $addlHeaders = [],
        string $method = 'post'
    ): array {
        if ($digest === '') {
            throw new RuntimeException(
                'Digest is missing.'
            );
        }

        return self::signRequest(
            privateKey: self::instanceActorPrivateKey(),
            keyId: self::instanceActorKeyId(),
            url: $url,
            body: false,
            addlHeaders: $addlHeaders,
            method: $method,
            digest: $digest
        );
    }

    /**
     * Parse a legacy Signature HTTP header.
     *
     * @return array<string, string>
     */
    public static function parseSignatureHeader(string $signature): array
    {
        $signature = trim($signature);

        if ($signature === '') {
            return [
                'error' => 'Signature header is empty.',
            ];
        }

        $signatureData = [];

        /*
         * Signature headers normally look like:
         *
         * keyId="...",
         * headers="(request-target) host date digest",
         * algorithm="rsa-sha256",
         * signature="..."
         *
         * Avoid explode(',') so parsing is not unnecessarily dependent on
         * commas never appearing inside quoted values.
         */
        preg_match_all(
            '/(?:^|,)\s*([A-Za-z][A-Za-z0-9_-]*)\s*=\s*"([^"]*)"/',
            $signature,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $signatureData[$match[1]] = $match[2];
        }

        if (! isset($signatureData['keyId'])) {
            return [
                'error' => 'No keyId was found in the signature header. Found: '.
                    implode(', ', array_keys($signatureData)),
            ];
        }

        $keyId = $signatureData['keyId'];

        if (! filter_var($keyId, FILTER_VALIDATE_URL)) {
            return [
                'error' => 'keyId is not a valid URL: '.$keyId,
            ];
        }

        if (! Helpers::validateUrl($keyId)) {
            return [
                'error' => 'keyId URL failed validation: '.$keyId,
            ];
        }

        if (
            ! isset($signatureData['headers']) ||
            trim($signatureData['headers']) === ''
        ) {
            return [
                'error' => 'Signature is missing the headers parameter.',
            ];
        }

        if (
            ! isset($signatureData['signature']) ||
            trim($signatureData['signature']) === ''
        ) {
            return [
                'error' => 'Signature is missing the signature parameter.',
            ];
        }

        return $signatureData;
    }

    /**
     * Verify an incoming legacy HTTP Signature.
     *
     * Existing five-argument callers remain compatible. $method was added as
     * an optional final argument so GET signatures can also be verified.
     *
     * $path should contain the complete request target including the query
     * string when present, for example:
     *
     *     /users/dan/inbox?foo=bar
     *
     * @param  array<string, mixed>  $signatureData
     * @param  array<string, mixed>  $inputHeaders
     * @return array{0: int|false, 1: string}
     */
    public static function verify(
        mixed $publicKey,
        array $signatureData,
        array $inputHeaders,
        string $path,
        string $body,
        string $method = 'post'
    ): array {
        $method = self::normalizeMethod($method);

        if (
            ! isset($signatureData['headers']) ||
            ! isset($signatureData['signature'])
        ) {
            return [0, ''];
        }

        $inputHeaders = self::normalizeInputHeaders(
            $inputHeaders
        );

        $digest = 'SHA-256='.self::_digest($body);

        $headersToSign = [];

        $signedHeaderNames = preg_split(
            '/\s+/',
            trim((string) $signatureData['headers'])
        );

        if (! is_array($signedHeaderNames)) {
            return [0, ''];
        }

        foreach ($signedHeaderNames as $headerName) {
            $headerName = strtolower(
                trim($headerName)
            );

            if ($headerName === '') {
                continue;
            }

            if ($headerName === '(request-target)') {
                $headersToSign[$headerName] =
                    $method.' '.self::normalizeRequestPath($path);

                continue;
            }

            /*
             * Calculate Digest ourselves from the exact received body.
             *
             * This means a signature using Digest can only verify if it was
             * generated against the body Pixelfed actually received.
             */
            if ($headerName === 'digest') {
                $headersToSign[$headerName] = $digest;

                continue;
            }

            if (! array_key_exists($headerName, $inputHeaders)) {
                /*
                 * A signed header is missing from the incoming request.
                 *
                 * There's no valid signing string we can reconstruct, so the
                 * verification must fail.
                 */
                return [
                    0,
                    self::_headersToSigningString($headersToSign),
                ];
            }

            $headersToSign[$headerName] =
                $inputHeaders[$headerName];
        }

        if (empty($headersToSign)) {
            return [0, ''];
        }

        $signingString = self::_headersToSigningString(
            $headersToSign
        );

        $signature = base64_decode(
            (string) $signatureData['signature'],
            true
        );

        if ($signature === false) {
            return [0, $signingString];
        }

        try {
            $verified = openssl_verify(
                $signingString,
                $signature,
                $publicKey,
                self::OPENSSL_ALGORITHM
            );
        } catch (Throwable) {
            return [0, $signingString];
        }

        return [
            $verified,
            $signingString,
        ];
    }

    /**
     * Build and cryptographically sign the HTTP signature headers.
     *
     * @param  array<string, mixed>  $addlHeaders
     * @return array<string, string>
     *
     * @throws JsonException
     */
    private static function signRequest(
        string $privateKey,
        string $keyId,
        string $url,
        mixed $body = false,
        array $addlHeaders = [],
        string $method = 'post',
        ?string $digest = null
    ): array {
        if ($privateKey === '') {
            throw new RuntimeException(
                'Private key is missing.'
            );
        }

        if ($keyId === '') {
            throw new RuntimeException(
                'Key ID is missing.'
            );
        }

        $method = self::normalizeMethod($method);

        /*
         * false / null mean "request has no body".
         *
         * This is intentionally different from:
         *
         *     if ($body)
         *
         * because valid payloads such as "", "0", or [] should not
         * accidentally disable Digest generation when explicitly supplied.
         */
        if (
            $digest === null &&
            self::hasBody($body)
        ) {
            $digest = self::_digest($body);
        }

        $headers = self::_headersToSign(
            $url,
            $digest,
            $method
        );

        $headers = self::mergeAdditionalHeaders(
            $headers,
            $addlHeaders
        );

        $stringToSign = self::_headersToSigningString(
            $headers
        );

        $signedHeaders = implode(
            ' ',
            array_map(
                static fn (string $header): string => strtolower($header),
                array_keys($headers)
            )
        );

        $key = openssl_pkey_get_private(
            $privateKey
        );

        if ($key === false) {
            throw new RuntimeException(
                'Private key is missing or invalid.'
            );
        }

        $signature = null;

        $signed = openssl_sign(
            $stringToSign,
            $signature,
            $key,
            self::OPENSSL_ALGORITHM
        );

        if (
            $signed !== true ||
            ! is_string($signature) ||
            $signature === ''
        ) {
            throw new RuntimeException(
                'Failed to generate HTTP signature.'
            );
        }

        $signatureHeader = sprintf(
            'keyId="%s",headers="%s",algorithm="%s",signature="%s"',
            $keyId,
            $signedHeaders,
            self::SIGNATURE_ALGORITHM,
            base64_encode($signature)
        );

        /*
         * (request-target) is a pseudo-header used only when constructing
         * the signing string. It must never be transmitted as an HTTP header.
         */
        unset($headers['(request-target)']);

        $headers['Signature'] = $signatureHeader;

        return $headers;
    }

    /**
     * Build the base headers used for signing.
     *
     * @return array<string, string>
     */
    protected static function _headersToSign(
        string $url,
        ?string $digest = null,
        string $method = 'post'
    ): array {
        $method = self::normalizeMethod($method);

        $parts = self::parseUrl($url);

        $date = new DateTimeImmutable(
            'now',
            new DateTimeZone('UTC')
        );

        $headers = [
            '(request-target)' => $method.' '.self::requestTargetFromParts($parts),
            'Host' => self::hostFromParts($parts),
            'Date' => $date->format('D, d M Y H:i:s \G\M\T'),
        ];

        if ($digest !== null && $digest !== '') {
            $headers['Digest'] = 'SHA-256='.$digest;
        }

        return $headers;
    }

    /**
     * Create the signing string.
     *
     * @param  array<string, string>  $headers
     */
    private static function _headersToSigningString(array $headers): string
    {
        $lines = [];

        foreach ($headers as $name => $value) {
            $lines[] = strtolower($name).': '.$value;
        }

        return implode("\n", $lines);
    }

    /**
     * Convert associative headers into cURL header syntax.
     *
     * @param  array<string, string>  $headers
     * @return array<int, string>
     */
    private static function _headersToCurlArray(array $headers): array
    {
        $result = [];

        foreach ($headers as $name => $value) {
            $result[] = $name.': '.$value;
        }

        return $result;
    }

    /**
     * Calculate the base64 encoded SHA-256 digest of the exact body.
     *
     * When an array/object is supplied directly it is encoded once using
     * PHP's normal JSON representation. Federation delivery code should
     * preferably serialize once itself and pass the resulting string here
     * so the signed digest and transmitted bytes are guaranteed identical.
     *
     * @throws JsonException
     */
    private static function _digest(mixed $body): string
    {
        if (! is_string($body)) {
            $body = json_encode(
                $body,
                JSON_THROW_ON_ERROR
            );
        }

        return base64_encode(
            hash(
                'sha256',
                $body,
                true
            )
        );
    }

    /**
     * Merge additional headers without allowing callers to replace headers
     * whose values define the request signature itself.
     *
     * Additional headers are matched case-insensitively.
     *
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $additional
     * @return array<string, string>
     */
    private static function mergeAdditionalHeaders(
        array $headers,
        array $additional
    ): array {
        $protected = [
            '(request-target)',
            'host',
            'date',
            'digest',
            'signature',
        ];

        $existing = [];

        foreach (array_keys($headers) as $name) {
            $existing[strtolower($name)] = $name;
        }

        foreach ($additional as $name => $value) {
            if (! is_string($name)) {
                continue;
            }

            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $normalizedName = strtolower($name);

            /*
             * Host, Date, Digest, etc. are derived from the actual request
             * being signed and must not be overridden by arbitrary headers.
             */
            if (in_array($normalizedName, $protected, true)) {
                continue;
            }

            $value = self::normalizeHeaderValue($value);

            if ($value === null) {
                continue;
            }

            /*
             * Preserve the first header's original casing while allowing an
             * additional header to update an existing non-protected value
             * case-insensitively.
             */
            if (isset($existing[$normalizedName])) {
                $headers[$existing[$normalizedName]] = $value;

                continue;
            }

            $headers[$name] = $value;
            $existing[$normalizedName] = $name;
        }

        return $headers;
    }

    /**
     * Normalize incoming request headers into:
     *
     * [
     *     'host' => 'example.org',
     *     'date' => '...',
     * ]
     *
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    private static function normalizeInputHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            if (! is_string($name)) {
                continue;
            }

            $headerName = strtolower(
                trim($name)
            );

            if ($headerName === '') {
                continue;
            }

            if (is_array($value)) {
                $values = [];

                foreach ($value as $item) {
                    $normalizedValue = self::normalizeHeaderValue($item);

                    if ($normalizedValue !== null) {
                        $values[] = $normalizedValue;
                    }
                }

                if (! empty($values)) {
                    $normalized[$headerName] = implode(', ', $values);
                }

                continue;
            }

            $normalizedValue = self::normalizeHeaderValue(
                $value
            );

            if ($normalizedValue !== null) {
                $normalized[$headerName] = $normalizedValue;
            }
        }

        return $normalized;
    }

    private static function normalizeHeaderValue(mixed $value): ?string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (
            is_int($value) ||
            is_float($value) ||
            is_bool($value)
        ) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return null;
    }

    /**
     * Parse and validate a URL used for HTTP signing.
     *
     * @return array<string, mixed>
     */
    private static function parseUrl(string $url): array
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            throw new InvalidArgumentException(
                'Unable to parse HTTP signature URL.'
            );
        }

        $scheme = strtolower(
            (string) ($parts['scheme'] ?? '')
        );

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException(
                'HTTP signature URL must use HTTP or HTTPS.'
            );
        }

        if (
            ! isset($parts['host']) ||
            ! is_string($parts['host']) ||
            $parts['host'] === ''
        ) {
            throw new InvalidArgumentException(
                'HTTP signature URL is missing a host.'
            );
        }

        return $parts;
    }

    /**
     * Generate the RFC request-target from a parsed URL.
     *
     * The query string is part of (request-target) and must be included:
     *
     *     POST /inbox?page=2
     *
     * Fragments are intentionally excluded because they are never sent in an
     * HTTP request.
     *
     * @param  array<string, mixed>  $parts
     */
    private static function requestTargetFromParts(array $parts): string
    {
        $path = $parts['path'] ?? '/';

        if (! is_string($path) || $path === '') {
            $path = '/';
        }

        if (
            array_key_exists('query', $parts) &&
            is_string($parts['query'])
        ) {
            $path .= '?'.$parts['query'];
        }

        return $path;
    }

    /**
     * Build the Host header, preserving an explicitly supplied port.
     *
     * @param  array<string, mixed>  $parts
     */
    private static function hostFromParts(array $parts): string
    {
        $host = (string) $parts['host'];

        if (
            str_contains($host, ':') &&
            ! str_starts_with($host, '[')
        ) {
            $host = '['.$host.']';
        }

        if (isset($parts['port'])) {
            $host .= ':'.(int) $parts['port'];
        }

        return $host;
    }

    /**
     * Normalize a request path supplied during verification.
     */
    private static function normalizeRequestPath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        if (
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return self::requestTargetFromParts(
                self::parseUrl($path)
            );
        }

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return $path;
    }

    private static function normalizeMethod(string $method): string
    {
        $method = strtolower(
            trim($method)
        );

        if (! in_array($method, self::SUPPORTED_METHODS, true)) {
            throw new InvalidArgumentException(
                'Invalid method used to sign HTTP request: '.$method
            );
        }

        return $method;
    }

    private static function hasBody(mixed $body): bool
    {
        return $body !== false &&
            $body !== null;
    }

    private static function instanceActorKeyId(): string
    {
        return rtrim(
            (string) config('app.url'),
            '/'
        ).'/i/actor#main-key';
    }

    private static function instanceActorPrivateKey(): string
    {
        return Cache::rememberForever(
            InstanceActor::PKI_PRIVATE,
            function (): string {
                $instance = InstanceActor::first();

                if (! $instance) {
                    Artisan::call('instance:actor');

                    $instance = InstanceActor::first();
                }

                if (
                    ! $instance ||
                    empty($instance->private_key)
                ) {
                    throw new RuntimeException(
                        'Failed to generate or retrieve the InstanceActor private key. '.
                            'Run: php artisan instance:actor'
                    );
                }

                return $instance->private_key;
            }
        );
    }
}
