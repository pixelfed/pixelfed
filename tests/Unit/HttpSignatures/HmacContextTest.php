<?php

namespace Tests\Unit\HttpSignatures;

use GuzzleHttp\Psr7\Request;
use App\Util\HttpSignatures\Context;

class HmacContextTest extends \PHPUnit\Framework\TestCase
{
    private $context;

    public function setUp()
    {
        $this->noDigestContext = new Context([
            'keys' => ['pda' => 'secret'],
            'algorithm' => 'hmac-sha256',
            'headers' => ['(request-target)', 'date'],
        ]);
        $this->withDigestContext = new Context([
            'keys' => ['pda' => 'secret'],
            'algorithm' => 'hmac-sha256',
            'headers' => ['(request-target)', 'date', 'digest'],
        ]);
    }

    public function testSignerNoDigestAction()
    {
        $message = new Request('GET', '/path?query=123', ['date' => 'today', 'accept' => 'llamas']);
        $message = $this->noDigestContext->signer()->sign($message);

        $expectedString = implode(',', [
            'keyId="pda"',
            'algorithm="hmac-sha256"',
            'headers="(request-target) date"',
            'signature="SFlytCGpsqb/9qYaKCQklGDvwgmrwfIERFnwt+yqPJw="',
        ]);

        $this->assertEquals(
            $expectedString,
            $message->getHeader('Signature')[0]
        );

        $this->assertEquals(
            'Signature '.$expectedString,
            $message->getHeader('Authorization')[0]
        );
    }

    public function testSignerAddDigestToHeadersList()
    {
        $message = new Request(
            'POST', '/path/to/things?query=123',
            ['date' => 'today', 'accept' => 'llamas'],
            'Thing to POST');
        $message = $this->noDigestContext->signer()->signWithDigest($message);

        $expectedString = implode(',', [
            'keyId="pda"',
            'algorithm="hmac-sha256"',
            'headers="(request-target) date digest"',
            'signature="HH6R3OJmJbKUFqqL0tGVIIb7xi1WbbSh/HBXHUtLkUs="', ]);
        $expectedDigestHeader =
          'SHA-256=rEcNhYZoBKiR29D30w1JcgArNlF8rXIXf5MnIL/4kcc=';

        $this->assertEquals(
            $expectedString,
            $message->getHeader('Signature')[0]
        );

        $this->assertEquals(
            $expectedDigestHeader,
            $message->getHeader('Digest')[0]
        );

        $this->assertEquals(
            'Signature '.$expectedString,
            $message->getHeader('Authorization')[0]
        );
    }

    public function testSignerReplaceDigest()
    {
        $message = new Request(
            'PUT', '/things/thething?query=123',
              ['date' => 'today',
              'accept' => 'llamas',
              'Digest' => 'SHA-256=E/P+4y4x6EySO9qNAjCtQKxVwE1xKsNI/k+cjK+vtLU=', ],
            'Thing to PUT at /things/thething please...');
        $message = $this->noDigestContext->signer()->signWithDigest($message);

        $expectedString = implode(',', [
            'keyId="pda"',
            'algorithm="hmac-sha256"',
            'headers="(request-target) date digest"',
            'signature="Hyatt1lSR/4XLI9Gcx8XOEKiG8LVktH7Lfr+0tmhwRU="', ]);
        $expectedDigestHeader =
          'SHA-256=mulOx+77mQU1EbPET50SCGA4P/4bYxVCJA1pTwJsaMw=';

        $this->assertEquals(
            $expectedString,
            $message->getHeader('Signature')[0]
        );

        $this->assertEquals(
            $expectedDigestHeader,
            $message->getHeader('Digest')[0]
        );

        $this->assertEquals(
            'Signature '.$expectedString,
            $message->getHeader('Authorization')[0]
        );
    }

    public function testSignerNewDigestIsInHeaderList()
    {
        $message = new Request(
            'POST', '/path?query=123',
              ['date' => 'today',
              'accept' => 'llamas', ],
            'Stuff that belongs in /path');
        $message = $this->withDigestContext->signer()->signWithDigest($message);

        $expectedString = implode(',', [
            'keyId="pda"',
            'algorithm="hmac-sha256"',
            'headers="(request-target) date digest"',
            'signature="p8gQHs59X2WzQLUecfmxm1YO0OBTCNKldRZZBQsepfk="', ]);
        $expectedDigestHeader =
          'SHA-256=jnSMEfBSum4Rh2k6/IVFyvLuQLmGYwMAGBS9WybyDqQ=';

        $this->assertEquals(
            $expectedString,
            $message->getHeader('Signature')[0]
        );

        $this->assertEquals(
            $expectedDigestHeader,
            $message->getHeader('Digest')[0]
        );

        $this->assertEquals(
            'Signature '.$expectedString,
            $message->getHeader('Authorization')[0]
        );
    }

    public function testSignerNewDigestWithoutBody()
    {
        $message = new Request(
            'GET', '/path?query=123',
              ['date' => 'today',
              'accept' => 'llamas', ]);
        $message = $this->withDigestContext->signer()->signWithDigest($message);

        $expectedString = implode(',', [
            'keyId="pda"',
            'algorithm="hmac-sha256"',
            'headers="(request-target) date digest"',
            'signature="7iFqqryI6I9opV/Zp3eEg6PDY1tKw/3GqioOM7ACHHA="', ]);
        $zeroLengthStringDigest =
          'SHA-256=47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU=';

        $this->assertEquals(
            $expectedString,
            $message->getHeader('Signature')[0]
        );

        $this->assertEquals(
            $zeroLengthStringDigest,
            $message->getHeader('Digest')[0]
        );

        $this->assertEquals(
            'Signature '.$expectedString,
            $message->getHeader('Authorization')[0]
        );
    }

    public function testVerifier()
    {
        $message = $this->noDigestContext->signer()->sign(new Request('GET', '/path?query=123', [
            'Signature' => 'keyId="pda",algorithm="hmac-sha1",headers="date",signature="x"',
            'Date' => 'x',
        ]));

        // assert it works without errors; correctness of results tested elsewhere.
        $this->assertTrue(is_bool($this->noDigestContext->verifier()->isValid($message)));
    }
}
