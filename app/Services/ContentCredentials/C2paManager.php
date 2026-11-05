<?php

namespace App\Services\ContentCredentials;

use App\Services\ContentCredentials\Contracts\C2paDriver;
use App\Services\ContentCredentials\Drivers\CliDriver;
use App\Services\ContentCredentials\Drivers\ExtDriver;
use App\Services\ContentCredentials\Drivers\NullDriver;

class C2paManager
{
    protected static ?C2paDriver $driver = null;

    public static function driver(): C2paDriver
    {
        return self::$driver ??= self::make((bool) config('c2pa.enabled') ? (string) config('c2pa.driver') : 'null');
    }

    public static function make(string $name): C2paDriver
    {
        $anchors = self::trustAnchors();

        return match ($name) {
            'cli' => new CliDriver(
                (string) config('c2pa.cli.binary', 'c2patool'),
                (int) config('c2pa.cli.timeout', 30),
                $anchors,
            ),
            'ext' => new ExtDriver($anchors),
            default => new NullDriver,
        };
    }

    public static function flush(): void
    {
        self::$driver = null;
    }

    protected static function trustAnchors(): ?string
    {
        $path = config('c2pa.trust_anchors');
        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return null;
        }

        $pem = file_get_contents($path);

        return is_string($pem) && str_contains($pem, 'BEGIN CERTIFICATE') ? $pem : null;
    }
}
