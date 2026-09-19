<?php

namespace App\Services\Config;

use App\Services\ConfigCacheService;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// Boot-time validator. Reads rules from ConfigCacheService and touches env/
// config only (no DB), so it is safe to run before migration.
class EnvConfigValidator
{
    // Throws when a present governed env var fails its rule. Missing rule warns.
    public static function validateBootEnv(): void
    {
        foreach (ConfigCacheService::keysInList('ENVCONFIG') as $key) {
            $envVar = ConfigCacheService::envVarFor($key);

            if ($envVar === null || ! self::envIsPresent($envVar)) {
                continue;
            }

            $rule = ConfigCacheService::ruleFor($key);

            if ($rule === null) {
                Log::warning("config-cache: no validation rule for {$key} (env {$envVar}); treated as valid.");

                continue;
            }

            if (! self::passesValue($rule, config($key))) {
                throw InvalidEnvironmentConfigException::forEnvVar($envVar, config($key), $rule);
            }
        }
    }

    public static function isValidEnvValue(string $key): bool
    {
        return self::isValidValue($key, config($key));
    }

    public static function isValidValue(string $key, mixed $value): bool
    {
        $rule = ConfigCacheService::ruleFor($key);

        return $rule === null ? true : self::passesValue($rule, $value);
    }

    protected static function envIsPresent(string $envVar): bool
    {
        $v = Env::get($envVar);

        return $v !== null && $v !== '';
    }

    protected static function passesValue(string $rule, mixed $value): bool
    {
        return Validator::make(['value' => $value], ['value' => $rule])->passes();
    }
}
