<?php

namespace App\Services\ContentCredentials\Drivers;

use App\Services\ContentCredentials\C2paException;
use App\Services\ContentCredentials\Contracts\C2paDriver;
use App\Services\ContentCredentials\SignRequest;
use Automattic\VIP\C2PA\Builder;
use Automattic\VIP\C2PA\Reader;
use Automattic\VIP\C2PA\Settings;
use Automattic\VIP\C2PA\Signer;

class ExtDriver implements C2paDriver
{
    public function __construct(
        protected ?string $trustAnchors = null,
    ) {}

    public function name(): string
    {
        return 'ext';
    }

    public function available(): bool
    {
        return extension_loaded('c2pa') && class_exists(Reader::class);
    }

    public function version(): ?string
    {
        return $this->available() ? (phpversion('c2pa') ?: null) : null;
    }

    public function canRedact(): bool
    {
        return false;
    }

    public function read(string $bytes, string $mime): ?array
    {
        $this->ensureAvailable();

        try {
            $reader = Reader::fromBytes($bytes, $this->mime($mime), $this->settings());
        } catch (\Throwable $e) {
            throw new C2paException('ext-c2pa read failed: '.$e->getMessage(), 0, $e);
        }

        if (! $reader->hasManifest()) {
            return null;
        }

        $store = json_decode($reader->json(), true);
        if (! is_array($store)) {
            throw new C2paException('ext-c2pa returned invalid JSON');
        }

        $store['validation_state'] ??= $reader->validationState();

        return $store;
    }

    public function sign(SignRequest $request): string
    {
        $this->ensureAvailable();

        if ($request->redactions !== []) {
            throw new C2paException('ext-c2pa can not redact parent assertions');
        }

        try {
            $builder = Builder::edit($request->title, $this->settings());
            $builder->withGenerator($request->generatorName);
            $builder->addIngredientFromBytes(
                json_encode(['title' => $request->parentTitle, 'relationship' => 'parentOf'], JSON_THROW_ON_ERROR),
                $this->mime($request->parentMime),
                $request->parentBytes
            );
            $builder->addAssertion('c2pa.actions', json_encode(['actions' => $request->actions], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $signer = Signer::fromPem($request->certPem, $request->keyPem, $request->alg);

            return $builder->sign($request->bytes, $this->mime($request->mime), $signer);
        } catch (\Throwable $e) {
            throw new C2paException('ext-c2pa sign failed: '.$e->getMessage(), 0, $e);
        }
    }

    protected function settings(): ?Settings
    {
        if (! $this->trustAnchors) {
            return null;
        }

        $settings = new Settings;
        $settings->withTrustAnchors($this->trustAnchors);

        return $settings;
    }

    protected function mime(string $mime): string
    {
        return $mime === 'image/jpg' ? 'image/jpeg' : $mime;
    }

    protected function ensureAvailable(): void
    {
        if (! $this->available()) {
            throw new C2paException('The c2pa PHP extension is not loaded');
        }
    }
}
