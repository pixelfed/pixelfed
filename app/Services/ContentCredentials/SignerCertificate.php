<?php

namespace App\Services\ContentCredentials;

class SignerCertificate
{
    protected const OPENSSL_CONFIG = <<<'CNF'
[ req ]
default_bits = 2048
distinguished_name = req_dn
prompt = no

[ req_dn ]

[ c2pa_ca ]
basicConstraints = critical, CA:TRUE, pathlen:0
keyUsage = critical, keyCertSign, cRLSign
subjectKeyIdentifier = hash

[ c2pa_leaf ]
basicConstraints = critical, CA:FALSE
keyUsage = critical, digitalSignature
extendedKeyUsage = emailProtection, 1.3.6.1.5.5.7.3.36
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid:always
CNF;

    public static function generate(string $commonName, string $organization, int $days = 1825): array
    {
        $config = tempnam(sys_get_temp_dir(), 'c2pa_cnf_');
        if ($config === false) {
            throw new C2paException('Could not create a temporary OpenSSL config');
        }

        file_put_contents($config, self::OPENSSL_CONFIG);

        try {
            $base = ['config' => $config, 'digest_alg' => 'sha256'];
            $keyOptions = $base + ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'];

            $caKey = self::check(openssl_pkey_new($keyOptions));
            $caCsr = self::check(openssl_csr_new([
                'commonName' => $organization.' Content Credentials CA',
                'organizationName' => $organization,
            ], $caKey, $base));
            $ca = self::check(openssl_csr_sign($caCsr, null, $caKey, $days + 365, $base + ['x509_extensions' => 'c2pa_ca'], self::serial()));

            $leafKey = self::check(openssl_pkey_new($keyOptions));
            $leafCsr = self::check(openssl_csr_new([
                'commonName' => $commonName,
                'organizationName' => $organization,
            ], $leafKey, $base));
            $leaf = self::check(openssl_csr_sign($leafCsr, $ca, $caKey, $days, $base + ['x509_extensions' => 'c2pa_leaf'], self::serial()));

            self::check(openssl_x509_export($leaf, $leafPem));
            self::check(openssl_x509_export($ca, $caPem));
            self::check(openssl_pkey_export($leafKey, $leafKeyPem, null, $base));
            self::check(openssl_pkey_export($caKey, $caKeyPem, null, $base));

            $info = openssl_x509_parse($leaf);

            return [
                'chain' => $leafPem.$caPem,
                'key' => $leafKeyPem,
                'ca' => $caPem,
                'ca_key' => $caKeyPem,
                'expires_at' => gmdate('Y-m-d\TH:i:s\Z', (int) ($info['validTo_time_t'] ?? 0)),
            ];
        } finally {
            @unlink($config);
        }
    }

    public static function describe(string $chainPem): ?array
    {
        $info = @openssl_x509_parse($chainPem);
        if (! is_array($info)) {
            return null;
        }

        $expires = (int) ($info['validTo_time_t'] ?? 0);

        return [
            'subject' => (string) ($info['subject']['CN'] ?? $info['name'] ?? ''),
            'issuer' => (string) ($info['issuer']['CN'] ?? ''),
            'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $expires),
            'expired' => $expires < time(),
        ];
    }

    protected static function serial(): int
    {
        return random_int(1, PHP_INT_MAX);
    }

    protected static function check(mixed $result): mixed
    {
        if ($result === false) {
            $errors = [];
            while ($e = openssl_error_string()) {
                $errors[] = $e;
            }

            throw new C2paException('OpenSSL: '.($errors ? implode('; ', $errors) : 'unknown error'));
        }

        return $result;
    }
}
