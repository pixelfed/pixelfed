<?php

namespace Tests\Unit\ContentCredentials;

use App\Services\ContentCredentials\ContentCredentialService;
use App\Services\ContentCredentials\DigitalSourceType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalSourceTypeTest extends TestCase
{
    #[Test]
    public function term_accepts_iptc_spellings(): void
    {
        $this->assertSame('trainedAlgorithmicMedia', DigitalSourceType::term('http://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia'));
        $this->assertSame('trainedAlgorithmicMedia', DigitalSourceType::term('https://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia/'));
        $this->assertSame('digitalCapture', DigitalSourceType::term('digitalCapture'));
    }

    #[Test]
    public function term_rejects_other_values(): void
    {
        $this->assertNull(DigitalSourceType::term('https://example.com/digitalsourcetype/trainedAlgorithmicMedia'));
        $this->assertNull(DigitalSourceType::term('http://cv.iptc.org/newscodes/digitalsourcetype/madeUp'));
        $this->assertNull(DigitalSourceType::term(['trainedAlgorithmicMedia']));
        $this->assertNull(DigitalSourceType::term(null));
    }

    #[Test]
    public function labels(): void
    {
        $this->assertSame('ai_generated', DigitalSourceType::labelFor('trainedAlgorithmicMedia'));
        $this->assertSame('ai_modified', DigitalSourceType::labelFor('compositeWithTrainedAlgorithmicMedia'));
        $this->assertNull(DigitalSourceType::labelFor('digitalCapture'));
        $this->assertNull(DigitalSourceType::labelFor(null));
    }

    #[Test]
    public function only_local_credentials_federate(): void
    {
        $local = ['content_credentials' => [
            'state' => 'trusted',
            'source' => 'local',
            'signer' => 'Camera Co',
            'generator' => 'Camera',
            'digital_source_type' => DigitalSourceType::uri('digitalCapture'),
        ]];

        $this->assertSame([
            'digitalSourceType' => 'http://cv.iptc.org/newscodes/digitalsourcetype/digitalCapture',
            'contentCredentials' => [
                'validationState' => 'Trusted',
                'signer' => 'Camera Co',
                'claimGenerator' => 'Camera',
            ],
        ], ContentCredentialService::activityPubProperties($local));

        $remote = $local;
        $remote['content_credentials']['source'] = 'remote';
        $this->assertSame([], ContentCredentialService::activityPubProperties($remote));

        $this->assertSame([], ContentCredentialService::activityPubProperties(['content_credentials' => null]));
    }
}
