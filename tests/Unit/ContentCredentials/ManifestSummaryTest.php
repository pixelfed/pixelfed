<?php

namespace Tests\Unit\ContentCredentials;

use App\Services\ContentCredentials\ManifestSummary;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManifestSummaryTest extends TestCase
{
    protected const REDACT = ['stds.exif', 'c2pa.metadata', 'c2pa.thumbnail.claim', 'c2pa.thumbnail.ingredient'];

    protected function store(array $manifests, string $active, string $state = 'Valid'): array
    {
        return [
            'active_manifest' => $active,
            'manifests' => $manifests,
            'validation_state' => $state,
        ];
    }

    protected function actions(array $actions): array
    {
        return ['label' => 'c2pa.actions.v2', 'data' => ['actions' => $actions]];
    }

    #[Test]
    public function ai_creation_in_an_ingredient_labels_the_edit(): void
    {
        $store = $this->store([
            'urn:c2pa:edit' => [
                'claim_generator_info' => [['name' => 'Photo Editor', 'version' => '2.0']],
                'signature_info' => ['issuer' => 'Editor Inc', 'common_name' => 'Editor Signer', 'time' => '2026-10-01T12:00:00+02:00'],
                'assertions' => [$this->actions([
                    ['action' => 'c2pa.opened'],
                    ['action' => 'c2pa.cropped'],
                ])],
                'ingredients' => [['relationship' => 'parentOf', 'active_manifest' => 'urn:c2pa:gen']],
            ],
            'urn:c2pa:gen' => [
                'claim_generator_info' => [['name' => 'Image Model']],
                'signature_info' => ['issuer' => 'Model Co'],
                'assertions' => [$this->actions([
                    ['action' => 'c2pa.created', 'digitalSourceType' => 'http://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia'],
                ])],
            ],
        ], 'urn:c2pa:edit');

        $summary = ManifestSummary::fromStore($store);

        $this->assertSame('Valid', $summary->state);
        $this->assertSame('Editor Inc', $summary->signer);
        $this->assertSame('Photo Editor', $summary->generator);
        $this->assertSame('2026-10-01T10:00:00Z', $summary->signedAt);
        $this->assertSame('trainedAlgorithmicMedia', $summary->digitalSourceType);
        $this->assertSame('ai_generated', $summary->aiLabel);
        $this->assertSame(['c2pa.opened', 'c2pa.cropped'], $summary->actions);
    }

    #[Test]
    public function generative_edit_of_a_photo_is_ai_modified(): void
    {
        $store = $this->store([
            'urn:c2pa:edit' => [
                'assertions' => [$this->actions([
                    ['action' => 'c2pa.opened'],
                    ['action' => 'c2pa.edited', 'digitalSourceType' => 'https://cv.iptc.org/newscodes/digitalsourcetype/compositeWithTrainedAlgorithmicMedia/'],
                ])],
                'ingredients' => [['relationship' => 'parentOf', 'active_manifest' => 'urn:c2pa:camera']],
            ],
            'urn:c2pa:camera' => [
                'assertions' => [$this->actions([
                    ['action' => 'c2pa.created', 'digitalSourceType' => 'http://cv.iptc.org/newscodes/digitalsourcetype/digitalCapture'],
                ])],
            ],
        ], 'urn:c2pa:edit');

        $summary = ManifestSummary::fromStore($store);

        $this->assertSame('compositeWithTrainedAlgorithmicMedia', $summary->digitalSourceType);
        $this->assertSame('ai_modified', $summary->aiLabel);
    }

    #[Test]
    public function camera_capture_has_no_label(): void
    {
        $store = $this->store([
            'urn:c2pa:cam' => [
                'claim_generator' => 'Camera_Firmware/1.0 c2pa-rs/0.20',
                'assertions' => [$this->actions([
                    ['action' => 'c2pa.created', 'digitalSourceType' => 'http://cv.iptc.org/newscodes/digitalsourcetype/digitalCapture'],
                ])],
            ],
        ], 'urn:c2pa:cam', 'Trusted');

        $summary = ManifestSummary::fromStore($store);

        $this->assertTrue($summary->isValid());
        $this->assertSame('Trusted', $summary->state);
        $this->assertSame('Camera_Firmware/1.0', $summary->generator);
        $this->assertSame('digitalCapture', $summary->digitalSourceType);
        $this->assertNull($summary->aiLabel);
    }

    #[Test]
    public function unknown_source_type_uris_are_ignored(): void
    {
        $store = $this->store([
            'urn:c2pa:a' => [
                'assertions' => [$this->actions([
                    ['action' => 'c2pa.created', 'digitalSourceType' => 'https://evil.example/digitalsourcetype/trainedAlgorithmicMedia'],
                ])],
            ],
        ], 'urn:c2pa:a');

        $summary = ManifestSummary::fromStore($store);

        $this->assertNull($summary->digitalSourceType);
        $this->assertNull($summary->aiLabel);
    }

    #[Test]
    public function state_falls_back_to_validation_results(): void
    {
        $manifests = ['urn:c2pa:a' => ['assertions' => []]];

        $invalid = ['active_manifest' => 'urn:c2pa:a', 'manifests' => $manifests, 'validation_results' => [
            'activeManifest' => ['success' => [], 'failure' => [['code' => 'assertion.dataHash.mismatch']]],
        ]];
        $this->assertSame('Invalid', ManifestSummary::fromStore($invalid)->state);

        $untrusted = ['active_manifest' => 'urn:c2pa:a', 'manifests' => $manifests, 'validation_status' => [
            ['code' => 'signingCredential.untrusted'],
        ]];
        $this->assertSame('Valid', ManifestSummary::fromStore($untrusted)->state);
    }

    #[Test]
    public function sensitive_assertions_are_found_across_the_chain(): void
    {
        $store = $this->store([
            'urn:c2pa:edit' => [
                'thumbnail' => ['identifier' => 'self#jumbf=/c2pa/urn:c2pa:edit/c2pa.assertions/c2pa.thumbnail.claim'],
                'assertions' => [
                    $this->actions([['action' => 'c2pa.opened']]),
                    ['label' => 'stds.exif__1', 'data' => ['exif:GPSLatitude' => '55,10.5N']],
                ],
                'ingredients' => [[
                    'relationship' => 'parentOf',
                    'active_manifest' => 'urn:c2pa:cam',
                    'thumbnail' => ['identifier' => 'self#jumbf=/c2pa/urn:c2pa:edit/c2pa.assertions/c2pa.thumbnail.ingredient.jpeg'],
                ]],
            ],
            'urn:c2pa:cam' => [
                'assertions' => [
                    ['label' => 'c2pa.metadata', 'data' => []],
                    ['label' => 'stds.schema-org.CreativeWork', 'data' => []],
                ],
            ],
        ], 'urn:c2pa:edit');

        $summary = ManifestSummary::fromStore($store, self::REDACT);

        $this->assertEqualsCanonicalizing([
            ['manifest' => 'urn:c2pa:edit', 'label' => 'c2pa.thumbnail.claim'],
            ['manifest' => 'urn:c2pa:edit', 'label' => 'c2pa.thumbnail.ingredient.jpeg'],
            ['manifest' => 'urn:c2pa:edit', 'label' => 'stds.exif__1'],
            ['manifest' => 'urn:c2pa:cam', 'label' => 'c2pa.metadata'],
        ], $summary->sensitive);
    }

    #[Test]
    public function ingredient_cycles_terminate(): void
    {
        $store = $this->store([
            'urn:c2pa:a' => ['assertions' => [], 'ingredients' => [['active_manifest' => 'urn:c2pa:b']]],
            'urn:c2pa:b' => ['assertions' => [], 'ingredients' => [['active_manifest' => 'urn:c2pa:a']]],
        ], 'urn:c2pa:a');

        $this->assertNotNull(ManifestSummary::fromStore($store));
    }

    #[Test]
    public function malformed_stores_return_null(): void
    {
        $this->assertNull(ManifestSummary::fromStore([]));
        $this->assertNull(ManifestSummary::fromStore(['active_manifest' => 'x', 'manifests' => []]));
        $this->assertNull(ManifestSummary::fromStore(['active_manifest' => ['x'], 'manifests' => ['x' => []]]));
    }
}
