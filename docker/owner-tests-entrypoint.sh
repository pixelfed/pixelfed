#!/bin/sh
set -eu

cd /var/www/html

test "${OWNER_TEST_SOURCE_SHA:-unknown}" != unknown
test "${OWNER_TEST_SOURCE_TREE:-unknown}" != unknown
test "${OWNER_TEST_COMPOSER_LOCK_SHA:-unknown}" != unknown
test -f .env.testing
test -x vendor/bin/pest

cp .env.testing .env
php artisan key:generate --force --ansi

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

php -r '
$key = openssl_pkey_new([
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
    "private_key_bits" => 2048,
]);
if ($key === false || !openssl_pkey_export($key, $privateKey)) {
    fwrite(STDERR, "Passport key generation failed\n");
    exit(1);
}
$details = openssl_pkey_get_details($key);
$publicKey = $details["key"] ?? null;
if (!is_string($publicKey)) {
    fwrite(STDERR, "Passport public key generation failed\n");
    exit(1);
}
file_put_contents("storage/oauth-private.key", $privateKey);
file_put_contents("storage/oauth-public.key", $publicKey);
chmod("storage/oauth-private.key", 0600);
chmod("storage/oauth-public.key", 0644);
'

test -s storage/oauth-private.key
test -s storage/oauth-public.key
test -r storage/oauth-private.key
test -r storage/oauth-public.key

printf '%s\n' "OWNER_TEST_SOURCE_SHA=${OWNER_TEST_SOURCE_SHA}"
printf '%s\n' "OWNER_TEST_SOURCE_TREE=${OWNER_TEST_SOURCE_TREE}"
printf '%s\n' "OWNER_TEST_COMPOSER_LOCK_SHA=${OWNER_TEST_COMPOSER_LOCK_SHA}"
printf '%s\n' "OWNER_TEST_PREPARATION=PASS"
printf '%s\n' "PASSPORT_TEST_PREPARATION=PASS"
printf '%s\n' "REDIS_HOST=${REDIS_HOST:-redis}"

exec vendor/bin/pest --compact "$@"
