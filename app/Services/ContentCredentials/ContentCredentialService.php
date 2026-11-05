<?php

namespace App\Services\ContentCredentials;

use App\Models\Media;
use App\Models\MediaContentCredential;
use App\Services\ContentCredentials\Contracts\C2paDriver;
use Illuminate\Support\Facades\Log;

class ContentCredentialService
{
    public const FEP = 'https://w3id.org/fep/5837';

    public const NOTE_CONTEXT_TERMS = [
        'digitalSourceType' => [
            '@id' => self::FEP.'#digitalSourceType',
            '@type' => '@id',
        ],
        'contentCredentials' => self::FEP.'#contentCredentials',
        'validationState' => self::FEP.'#validationState',
        'signer' => self::FEP.'#signer',
        'claimGenerator' => self::FEP.'#claimGenerator',
    ];

    protected const SIGNABLE = ['image/jpeg', 'image/png', 'image/webp'];

    protected static ?bool $available = null;

    public static function driver(): C2paDriver
    {
        return C2paManager::driver();
    }

    public static function enabled(): bool
    {
        if (! (bool) config('c2pa.enabled')) {
            return false;
        }

        return self::$available ??= self::driver()->available();
    }

    public static function signingEnabled(): bool
    {
        return self::enabled() && (bool) config('c2pa.sign.enabled');
    }

    public static function inspect(Media $media, string $bytes): ?ManifestSummary
    {
        if ($media->remote_media || ! self::enabled()) {
            return null;
        }

        try {
            $store = self::driver()->read($bytes, self::mime($media->mime));
        } catch (\Throwable $e) {
            Log::warning("ContentCredentials: could not read media {$media->id}: ".$e->getMessage());

            return null;
        }

        if ($store === null) {
            return null;
        }

        $summary = ManifestSummary::fromStore($store, self::redactLabels());
        if ($summary === null) {
            return null;
        }

        if (! MediaContentCredential::whereMediaId($media->id)->exists()) {
            MediaContentCredential::create([
                'media_id' => $media->id,
                'source' => MediaContentCredential::SOURCE_LOCAL,
                'state' => $summary->state,
                'signer' => $summary->signer,
                'generator' => $summary->generator,
                'signed_at' => $summary->signedAt,
                'digital_source_type' => $summary->digitalSourceType,
                'ai_label' => $summary->aiLabel,
                'actions' => $summary->actions,
            ]);
        }

        return $summary;
    }

    public static function signDerivative(
        Media $media,
        ?ManifestSummary $parent,
        string $derivative,
        string $derivativeMime,
        string $title,
        string $original,
        string $originalMime,
        bool $resized,
    ): ?string {
        if ($media->remote_media || ! self::signingEnabled()) {
            return null;
        }

        $derivativeMime = self::mime($derivativeMime);
        $originalMime = self::mime($originalMime);

        if (! in_array($derivativeMime, self::SIGNABLE, true)) {
            return null;
        }

        if (config('c2pa.sign.mode') !== 'all' && ! $parent?->isValid()) {
            return null;
        }

        $driver = self::driver();
        $sensitive = $parent ? $parent->sensitive : [];

        if ($sensitive !== [] && ! $driver->canRedact()) {
            Log::info("ContentCredentials: not signing media {$media->id}, the {$driver->name()} driver can not redact sensitive parent assertions");

            return null;
        }

        $cert = self::readKeyFile((string) config('c2pa.sign.cert'));
        $key = self::readKeyFile((string) config('c2pa.sign.key'));
        if ($cert === null || $key === null) {
            Log::warning('ContentCredentials: signing is enabled but the signing certificate or key is missing, run php artisan c2pa:generate-signer');

            return null;
        }

        $agent = ['name' => 'Pixelfed', 'version' => (string) config('pixelfed.version')];
        $actions = [];
        if ($resized) {
            $actions[] = ['action' => 'c2pa.resized', 'softwareAgent' => $agent];
        }
        if ($derivativeMime !== $originalMime) {
            $actions[] = ['action' => 'c2pa.converted', 'softwareAgent' => $agent];
        }
        $actions[] = ['action' => 'c2pa.transcoded', 'softwareAgent' => $agent];

        $redactions = array_map(
            fn (array $a) => "self#jumbf=/c2pa/{$a['manifest']}/c2pa.assertions/{$a['label']}",
            $sensitive
        );

        try {
            $signed = $driver->sign(new SignRequest(
                bytes: $derivative,
                mime: $derivativeMime,
                title: $title,
                parentBytes: $original,
                parentMime: $originalMime,
                parentTitle: 'original',
                actions: $actions,
                redactions: $redactions,
                generatorName: 'Pixelfed',
                generatorVersion: (string) config('pixelfed.version'),
                certPem: $cert,
                keyPem: $key,
                alg: (string) config('c2pa.sign.alg', 'es256'),
                tsaUrl: config('c2pa.sign.tsa_url') ?: null,
            ));
        } catch (\Throwable $e) {
            Log::warning("ContentCredentials: could not sign media {$media->id}: ".$e->getMessage());

            return null;
        }

        MediaContentCredential::updateOrCreate(
            ['media_id' => $media->id],
            ['instance_signed_at' => now()]
        );

        return $signed;
    }

    public static function isInstanceSigned(Media $media): bool
    {
        return MediaContentCredential::whereMediaId($media->id)
            ->whereNotNull('instance_signed_at')
            ->exists();
    }

    public static function forApi(?MediaContentCredential $c): ?array
    {
        if ($c === null) {
            return null;
        }

        if ($c->source === MediaContentCredential::SOURCE_REMOTE && ! (bool) config('c2pa.remote')) {
            return null;
        }

        return [
            'state' => $c->state ? strtolower($c->state) : null,
            'source' => $c->source,
            'signer' => $c->signer,
            'generator' => $c->generator,
            'signed_at' => $c->signed_at?->format('Y-m-d\TH:i:s\Z'),
            'digital_source_type' => DigitalSourceType::uri($c->digital_source_type),
            'ai_label' => (bool) config('c2pa.ai_label') ? $c->ai_label : null,
            'instance_signed' => $c->instance_signed_at !== null,
        ];
    }

    public static function activityPubProperties(array $media): array
    {
        $c = $media['content_credentials'] ?? null;
        if (! is_array($c) || ($c['source'] ?? null) !== MediaContentCredential::SOURCE_LOCAL) {
            return [];
        }

        $res = [];

        if (! empty($c['digital_source_type'])) {
            $res['digitalSourceType'] = $c['digital_source_type'];
        }

        if (! empty($c['state'])) {
            $res['contentCredentials'] = array_filter([
                'validationState' => ucfirst($c['state']),
                'signer' => $c['signer'] ?? null,
                'claimGenerator' => $c['generator'] ?? null,
            ]);
        }

        return $res;
    }

    public static function storeRemote(Media $media, array $attachment): void
    {
        if (! (bool) config('c2pa.remote')) {
            return;
        }

        $term = DigitalSourceType::term($attachment['digitalSourceType'] ?? null);

        $cc = is_array($attachment['contentCredentials'] ?? null) ? $attachment['contentCredentials'] : [];
        $state = $cc['validationState'] ?? null;
        $state = in_array($state, [ManifestSummary::STATE_TRUSTED, ManifestSummary::STATE_VALID, ManifestSummary::STATE_INVALID], true) ? $state : null;

        if ($term === null && $state === null) {
            return;
        }

        MediaContentCredential::updateOrCreate(
            ['media_id' => $media->id],
            [
                'source' => MediaContentCredential::SOURCE_REMOTE,
                'state' => $state,
                'signer' => $state ? self::clean($cc['signer'] ?? null) : null,
                'generator' => $state ? self::clean($cc['claimGenerator'] ?? null) : null,
                'digital_source_type' => $term,
                'ai_label' => DigitalSourceType::labelFor($term),
            ]
        );
    }

    public static function delete(Media $media): void
    {
        MediaContentCredential::whereMediaId($media->id)->delete();
    }

    public static function flush(): void
    {
        self::$available = null;
        C2paManager::flush();
    }

    protected static function redactLabels(): array
    {
        $labels = config('c2pa.sign.redact', []);

        return is_array($labels) ? array_values(array_filter($labels, 'is_string')) : [];
    }

    protected static function readKeyFile(string $path): ?string
    {
        if ($path === '' || ! is_readable($path)) {
            return null;
        }

        $pem = file_get_contents($path);

        return is_string($pem) && str_contains($pem, '-----BEGIN') ? $pem : null;
    }

    protected static function mime(?string $mime): string
    {
        return $mime === 'image/jpg' ? 'image/jpeg' : (string) $mime;
    }

    protected static function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(strip_tags($value));

        return $value === '' ? null : mb_substr($value, 0, 191);
    }
}
