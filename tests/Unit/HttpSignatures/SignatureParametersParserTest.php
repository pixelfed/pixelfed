<?php

namespace Tests\Unit\HttpSignatures;

use App\Util\HttpSignatures\SignatureParametersParser;

class SignatureParametersParserTest extends \PHPUnit\Framework\TestCase
{
    public function testParseReturnsExpectedAssociativeArray()
    {
        $parser = new SignatureParametersParser(
            'keyId="example",algorithm="hmac-sha1",headers="(request-target) date",signature="b64"'
        );
        $this->assertEquals(
            [
                'keyId' => 'example',
                'algorithm' => 'hmac-sha1',
                'headers' => '(request-target) date',
                'signature' => 'b64',
            ],
            $parser->parse()
        );
    }

    /**
     * @expectedException App\Util\HttpSignatures\SignatureParseException
     */
    public function testParseThrowsTypedException()
    {
        $parser = new SignatureParametersParser('nope');
        $parser->parse();
    }

    /**
     * @expectedException App\Util\HttpSignatures\SignatureParseException
     */
    public function testParseExceptionForMissingComponents()
    {
        $parser = new SignatureParametersParser(
            'keyId="example",algorithm="hmac-sha1",headers="(request-target) date"'
        );
        $parser->parse();
    }
}
