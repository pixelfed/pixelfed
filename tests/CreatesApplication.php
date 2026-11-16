<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Hash;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $this->useParallelRedisDatabase();

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        Hash::driver('bcrypt')->setRounds(4);

        return $app;
    }

    protected function useParallelRedisDatabase(): void
    {
        $token = $_SERVER['TEST_TOKEN'] ?? getenv('TEST_TOKEN');

        if (! is_string($token) && ! is_int($token)) {
            return;
        }

        if (! ctype_digit((string) $token) || (int) $token < 1) {
            return;
        }

        $database = (string) (((int) $token - 1) % 16);

        $_SERVER['REDIS_DB'] = $_ENV['REDIS_DB'] = $database;
        putenv("REDIS_DB={$database}");
    }
}
