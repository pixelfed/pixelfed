<?php

namespace App\Support;

class MemoryLimit
{
    public static function raise(string|int|null $limit): bool
    {
        $requested = self::toBytes($limit);

        if ($requested === null) {
            return false;
        }

        $current = self::toBytes(ini_get('memory_limit'));

        if ($current === -1) {
            return false;
        }

        $ceiling = self::ceiling();

        if ($ceiling !== null) {
            $requested = $requested === -1 ? $ceiling : min($requested, $ceiling);
        }

        if ($requested !== -1 && $current !== null && $requested <= $current) {
            return false;
        }

        return @ini_set('memory_limit', (string) $requested) !== false;
    }

    public static function ceiling(): ?int
    {
        $ceiling = self::toBytes(ini_get('max_memory_limit'));

        return $ceiling === null || $ceiling === -1 ? null : $ceiling;
    }

    public static function toBytes(string|int|false|null $value): ?int
    {
        if ($value === null || $value === false) {
            return null;
        }

        if (is_int($value)) {
            return $value < 0 ? -1 : $value;
        }

        $value = trim($value);

        if (! preg_match('/^-?\d+[kmg]?$/i', $value)) {
            return null;
        }

        $bytes = ini_parse_quantity($value);

        if ($bytes < 0) {
            return -1;
        }

        return $bytes > 0 ? $bytes : null;
    }
}
