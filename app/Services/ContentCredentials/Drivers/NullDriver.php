<?php

namespace App\Services\ContentCredentials\Drivers;

use App\Services\ContentCredentials\C2paException;
use App\Services\ContentCredentials\Contracts\C2paDriver;
use App\Services\ContentCredentials\SignRequest;

class NullDriver implements C2paDriver
{
    public function name(): string
    {
        return 'null';
    }

    public function available(): bool
    {
        return false;
    }

    public function version(): ?string
    {
        return null;
    }

    public function read(string $bytes, string $mime): ?array
    {
        return null;
    }

    public function canRedact(): bool
    {
        return false;
    }

    public function sign(SignRequest $request): string
    {
        throw new C2paException('Content Credentials are disabled');
    }
}
