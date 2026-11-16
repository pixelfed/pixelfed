<?php

namespace Tests;

use App\Util\ActivityPub\KeyPair;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Sleep;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    private const KEY_PAIR_POOL_SIZE = 8;

    private static array $keyPairPool = [];

    private static int $keyPairCursor = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();

        KeyPair::generateUsing(static fn (): array => self::pooledKeyPair());
    }

    protected function tearDown(): void
    {
        KeyPair::generateUsing(null);

        parent::tearDown();
    }

    private static function pooledKeyPair(): array
    {
        if (count(self::$keyPairPool) < self::KEY_PAIR_POOL_SIZE) {
            return self::$keyPairPool[] = KeyPair::create();
        }

        return self::$keyPairPool[self::$keyPairCursor++ % self::KEY_PAIR_POOL_SIZE];
    }
}
