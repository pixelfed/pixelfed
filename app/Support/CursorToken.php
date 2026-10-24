<?php

namespace App\Support;

class CursorToken
{
    public static function encode(?string $laravelCursor, string $ctx, int $hops, int $ttlSeconds = 300): ?string
    {
        if (! $laravelCursor) {
            return null;
        }

        $payload = [
            'c' => $laravelCursor,
            'h' => $hops,
            'e' => time() + $ttlSeconds,
            'x' => $ctx,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

        $sig = hash_hmac('sha256', $b64, config('app.key'));

        return $b64.'.'.$sig;
    }

    public static function decode(string $token, string $ctx): array
    {
        [$b64, $sig] = array_pad(explode('.', $token, 2), 2, null);
        if (! $b64 || ! $sig) {
            abort(400, 'Invalid cursor.');
        }

        $expected = hash_hmac('sha256', $b64, config('app.key'));
        if (! hash_equals($expected, $sig)) {
            abort(400, 'Invalid cursor.');
        }

        $json = base64_decode(strtr($b64, '-_', '+/'), true);
        if ($json === false) {
            abort(400, 'Invalid cursor.');
        }

        $payload = json_decode($json, true);
        if (! is_array($payload)) {
            abort(400, 'Invalid cursor.');
        }

        if (($payload['x'] ?? null) !== $ctx) {
            abort(400, 'Cursor does not match this query.');
        }

        if (($payload['e'] ?? 0) < time()) {
            abort(400, 'Cursor expired.');
        }

        return [
            'cursor' => (string) ($payload['c'] ?? ''),
            'hops' => (int) ($payload['h'] ?? 0),
        ];
    }
}
