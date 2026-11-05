<?php

namespace App\Services\ContentCredentials;

final class SignRequest
{
    public function __construct(
        public readonly string $bytes,
        public readonly string $mime,
        public readonly string $title,
        public readonly string $parentBytes,
        public readonly string $parentMime,
        public readonly string $parentTitle,
        public readonly array $actions,
        public readonly array $redactions,
        public readonly string $generatorName,
        public readonly string $generatorVersion,
        public readonly string $certPem,
        public readonly string $keyPem,
        public readonly string $alg,
        public readonly ?string $tsaUrl = null,
    ) {}
}
