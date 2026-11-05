<?php

namespace Automattic\VIP\C2PA;

class Settings
{
    public function withTrustAnchors(string $pem): void {}

    public function hasTrustAnchors(): bool {}
}

class Reader
{
    public static function fromBytes(string $bytes, string $mime, ?Settings $settings = null): Reader {}

    public function hasManifest(): bool {}

    public function validationState(): string {}

    public function json(): string {}
}

class Signer
{
    public static function fromPem(string $certChainPem, string $privateKeyPem, string $alg): Signer {}
}

class Builder
{
    public static function edit(string $title, ?Settings $settings = null): Builder {}

    public function withGenerator(string $name): void {}

    public function addAssertion(string $label, string $json): void {}

    public function addIngredientFromBytes(string $ingredientJson, string $mime, string $bytes): void {}

    public function sign(string $bytes, string $mime, Signer $signer): string {}
}

class C2paException extends \RuntimeException {}
