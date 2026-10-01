<?php

namespace App\Services\Config;

use RuntimeException;

class InvalidEnvironmentConfigException extends RuntimeException
{
    public static function forEnvVar(string $envVar, mixed $value, string $rule): self
    {
        $printable = self::printableValue($value);

        $message = sprintf(
            'Invalid environment configuration: %s=%s is not allowed. Expected: %s. '.
            'Fix the value in your .env (or environment) and restart.',
            $envVar,
            $printable,
            $rule
        );

        return new self($message);
    }

    protected static function printableValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value) ?: gettype($value);
    }
}
