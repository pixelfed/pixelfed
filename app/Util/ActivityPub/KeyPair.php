<?php

namespace App\Util\ActivityPub;

use Closure;
use RuntimeException;

class KeyPair
{
    protected static ?Closure $generator = null;

    public static function generate(): array
    {
        if (static::$generator !== null) {
            return (static::$generator)();
        }

        return static::create();
    }

    public static function create(): array
    {
        $pki = openssl_pkey_new([
            'digest_alg' => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($pki === false || ! openssl_pkey_export($pki, $private)) {
            throw new RuntimeException('Unable to generate an RSA key pair.');
        }

        $details = openssl_pkey_get_details($pki);

        if ($details === false || empty($details['key'])) {
            throw new RuntimeException('Unable to read the generated RSA public key.');
        }

        return [
            'private' => $private,
            'public' => $details['key'],
        ];
    }

    public static function generateUsing(?callable $generator): void
    {
        static::$generator = $generator === null ? null : Closure::fromCallable($generator);
    }
}
