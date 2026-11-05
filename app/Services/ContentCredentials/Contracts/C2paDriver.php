<?php

namespace App\Services\ContentCredentials\Contracts;

use App\Services\ContentCredentials\SignRequest;

interface C2paDriver
{
    public function name(): string;

    public function available(): bool;

    public function version(): ?string;

    public function read(string $bytes, string $mime): ?array;

    public function canRedact(): bool;

    public function sign(SignRequest $request): string;
}
