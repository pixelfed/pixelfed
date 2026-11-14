<?php

namespace App\Services\ContentCredentials;

final class ManifestSummary
{
    public const STATE_TRUSTED = 'Trusted';

    public const STATE_VALID = 'Valid';

    public const STATE_INVALID = 'Invalid';

    protected const MAX_DEPTH = 16;

    public function __construct(
        public readonly string $state,
        public readonly ?string $signer,
        public readonly ?string $generator,
        public readonly ?string $signedAt,
        public readonly ?string $digitalSourceType,
        public readonly ?string $aiLabel,
        public readonly array $actions,
        public readonly array $sensitive,
    ) {}

    public function isValid(): bool
    {
        return $this->state === self::STATE_VALID || $this->state === self::STATE_TRUSTED;
    }

    public static function fromStore(array $store, array $redact = []): ?self
    {
        $manifests = $store['manifests'] ?? null;
        $active = $store['active_manifest'] ?? null;

        if (! is_array($manifests) || ! is_string($active) || ! isset($manifests[$active]) || ! is_array($manifests[$active])) {
            return null;
        }

        $root = $manifests[$active];

        $walk = [
            'actions' => [],
            'created' => null,
            'generated' => false,
            'modified' => false,
            'sensitive' => [],
            'seen' => [],
        ];

        self::walk($manifests, $active, 0, $redact, $walk, true);

        $dst = match (true) {
            $walk['generated'] => 'trainedAlgorithmicMedia',
            $walk['modified'] => 'compositeWithTrainedAlgorithmicMedia',
            default => $walk['created'],
        };

        $label = match (true) {
            $walk['generated'] => DigitalSourceType::LABEL_AI_GENERATED,
            $walk['modified'] => DigitalSourceType::LABEL_AI_MODIFIED,
            default => null,
        };

        $sig = is_array($root['signature_info'] ?? null) ? $root['signature_info'] : [];

        return new self(
            state: self::state($store),
            signer: self::str($sig['issuer'] ?? null) ?? self::str($sig['common_name'] ?? null),
            generator: self::generator($root),
            signedAt: self::time($sig['time'] ?? null),
            digitalSourceType: $dst,
            aiLabel: $label,
            actions: array_values(array_unique($walk['actions'])),
            sensitive: array_values($walk['sensitive']),
        );
    }

    /**
     * The parent chain (the active manifest, then each parentOf ingredient in
     * turn) decides whether the asset itself was created by an AI model.
     * Other ingredients, such as componentOf parts placed into the asset, can
     * only make it AI modified: a photo with one generated element placed into
     * it is not AI generated.
     */
    protected static function walk(array $manifests, string $label, int $depth, array $redact, array &$walk, bool $parentChain): void
    {
        if ($depth > self::MAX_DEPTH || ! is_array($manifests[$label] ?? null)) {
            return;
        }

        // A manifest first reached outside the parent chain is walked again if
        // the chain reaches it later, since only the chain can set 'created'.
        if (isset($walk['seen'][$label]) && ($walk['seen'][$label] || ! $parentChain)) {
            return;
        }

        $walk['seen'][$label] = $parentChain;
        $manifest = $manifests[$label];

        $thumbnails = [$manifest['thumbnail']['identifier'] ?? null];
        foreach (($manifest['ingredients'] ?? []) as $ingredient) {
            $thumbnails[] = is_array($ingredient) ? ($ingredient['thumbnail']['identifier'] ?? null) : null;
        }

        foreach ($thumbnails as $identifier) {
            $assertionLabel = is_string($identifier) ? self::assertionFromUri($identifier) : null;
            if ($assertionLabel !== null && self::matchesAny($assertionLabel, $redact)) {
                $owner = preg_match('#/c2pa/([^/]+)/c2pa\.assertions/#', $identifier, $m) ? $m[1] : $label;
                $walk['sensitive'][$owner.'/'.$assertionLabel] = ['manifest' => $owner, 'label' => $assertionLabel];
            }
        }

        foreach (($manifest['assertions'] ?? []) as $assertion) {
            if (! is_array($assertion) || ! is_string($assertion['label'] ?? null)) {
                continue;
            }

            $assertionLabel = $assertion['label'];

            if (self::matchesAny($assertionLabel, $redact)) {
                $walk['sensitive'][$label.'/'.$assertionLabel] = ['manifest' => $label, 'label' => $assertionLabel];

                continue;
            }

            if (! str_starts_with($assertionLabel, 'c2pa.actions')) {
                continue;
            }

            foreach (($assertion['data']['actions'] ?? []) as $action) {
                if (! is_array($action) || ! is_string($action['action'] ?? null)) {
                    continue;
                }

                $name = $action['action'];

                if ($depth === 0) {
                    $walk['actions'][] = substr($name, 0, 64);
                }

                $term = DigitalSourceType::term($action['digitalSourceType'] ?? null);
                if ($term === null) {
                    continue;
                }

                if ($parentChain && $name === 'c2pa.created') {
                    $walk['created'] ??= $term;
                }

                if ($parentChain && $name === 'c2pa.created' && $term === 'trainedAlgorithmicMedia') {
                    $walk['generated'] = true;
                } elseif (DigitalSourceType::isAi($term)) {
                    $walk['modified'] = true;
                }
            }
        }

        $ingredients = array_filter($manifest['ingredients'] ?? [], 'is_array');

        // Walk parentOf ingredients first so the chain claims shared manifests.
        usort($ingredients, fn (array $a, array $b) => self::isParent($b) <=> self::isParent($a));

        foreach ($ingredients as $ingredient) {
            $child = $ingredient['active_manifest'] ?? $ingredient['manifest'] ?? null;
            if (is_string($child)) {
                self::walk($manifests, $child, $depth + 1, $redact, $walk, $parentChain && self::isParent($ingredient));
            }
        }
    }

    protected static function isParent(array $ingredient): bool
    {
        return ($ingredient['relationship'] ?? null) === 'parentOf';
    }

    protected static function state(array $store): string
    {
        $state = $store['validation_state'] ?? null;
        if (in_array($state, [self::STATE_TRUSTED, self::STATE_VALID, self::STATE_INVALID], true)) {
            return $state;
        }

        $failures = $store['validation_results']['activeManifest']['failure'] ?? null;
        if (is_array($failures)) {
            return $failures === [] ? self::STATE_VALID : self::STATE_INVALID;
        }

        foreach (($store['validation_status'] ?? []) as $status) {
            $code = is_array($status) ? ($status['code'] ?? null) : null;
            if ($code !== 'signingCredential.untrusted') {
                return self::STATE_INVALID;
            }
        }

        return self::STATE_VALID;
    }

    protected static function generator(array $manifest): ?string
    {
        $info = $manifest['claim_generator_info'][0] ?? null;
        if (is_array($info) && ($name = self::str($info['name'] ?? null))) {
            return $name;
        }

        $legacy = self::str($manifest['claim_generator'] ?? null);

        return $legacy === null ? null : self::str(explode(' ', $legacy)[0]);
    }

    protected static function time(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        } catch (\Exception) {
            return null;
        }
    }

    protected static function str(mixed $value, int $max = 191): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '');

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    protected static function matchesAny(string $label, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($label === $prefix || str_starts_with($label, $prefix.'__') || str_starts_with($label, $prefix.'.')) {
                return true;
            }
        }

        return false;
    }

    protected static function assertionFromUri(string $uri): ?string
    {
        $pos = strrpos($uri, 'c2pa.assertions/');
        if ($pos === false) {
            return null;
        }

        $label = substr($uri, $pos + strlen('c2pa.assertions/'));

        return preg_match('/^[A-Za-z0-9._-]{1,128}$/', $label) ? $label : null;
    }
}
