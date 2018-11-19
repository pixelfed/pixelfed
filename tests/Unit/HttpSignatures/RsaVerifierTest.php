<?php

namespace Tests\Unit\HttpSignatures;

use GuzzleHttp\Psr7\Request;
use App\Util\HttpSignatures\KeyStore;
use App\Util\HttpSignatures\Verifier;
use Tests\Unit\HttpSignatures\TestKeys;

class VerifierRsaTest extends \PHPUnit\Framework\TestCase
{
    const DATE = 'Fri, 01 Aug 2014 13:44:32 -0700';
    const DATE_DIFFERENT = 'Fri, 01 Aug 2014 13:44:33 -0700';

    /**
     * @var Verifier
     */
    private $verifier;

    /**
     * @var Request
     */
    private $message;

    public function setUp()
    {
        $this->setUpRsaVerifier();

        $sha1SignatureHeader =
        'keyId="rsa1",algorithm="rsa-sha1",headers="(request-target) date",'.
        'signature="YIR3DteE3Jmz1VAnUMTgjTn3vTKfQuZl1CJhMBvGOZpnzwKeYBXAH10'.
        '8FojnbSeVG/AXq9pcrA6AFK0peg0aueqxpaFlo+4L/q5XzJ+QoryY3dlSrxwVnE5s5'.
        'M19xmFm/6YkZR/KPeANCsG4SPL82Um/PCEMU0tmKd6sSx+IIzAYbXG/VrFMDeQAdXq'.
        'pU1EhgxopKEAapN8rChb49+1JfR/RxlSKiLukJJ6auurm2zMn2D40fR1d2umA5LAO7'.
        'vRt2iQwVbtwiFkVlRqkMvGftCNZByu8jJ6StI5H7EfuANSHAZXKXWNH8yxpBUW/QCH'.
        'CZjPd0ugM0QJJIc7i8JbGlA=="';

        $this->sha1Message = new Request('GET', '/path?query=123', [
            'Date' => 'today',
            'Signature' => $sha1SignatureHeader,
        ]);

        $sha256SignatureHeader =
        'keyId="rsa1",algorithm="rsa-sha256",headers="(request-target) date",'.
        'signature="WGIegQCC3GEwxbkuXtq67CAqeDhkwblxAH2uoDx5kfWurhLRA5WBFDA/a'.
        'ktsZAjuUoimG1w4CGxSecziER1ez44PBlHP2fCW4ArLgnQgcjkdN2cOf/gj0OVL8s2us'.
        'G4o4tud/+jjF3nxTxLl3HC+erBKsJakwXbw9kt4Cr028BToVfNXsWoMFpv0IjcgBH2V4'.
        '1AVlX/mYBMMJAihBCIcpgAcGrrxmG2gkfvSn09wtTttkGHftPIp3VpB53zbemlJS9Yw3'.
        'tmmHr6cvWSXqQy/bTsEOoQJ2REfn5eiyzsJu3GiOpiILK67i/WH9moltJtlfV57TV72c'.
        'gYtjWa6yqhtFg=="';

        $this->sha256Message = new Request('GET', '/path?query=123', [
            'Date' => 'today',
            'Signature' => $sha256SignatureHeader,
        ]);
    }

    private function setUpRsaVerifier()
    {
        $keyStore = new KeyStore(['rsa1' => TestKeys::rsaPublicKey]);
        $this->verifier = new Verifier($keyStore);
    }

    public function testVerifyValidRsaMessage()
    {
        $this->assertTrue($this->verifier->isValid($this->sha1Message));
        $this->assertTrue($this->verifier->isValid($this->sha256Message));
    }

    public function testVerifyValidRsaMessageAuthorizationHeader()
    {
        $message = $this->sha1Message->withHeader(
          'Authorization',
          "Signature {$this->sha1Message->getHeader('Signature')[0]}");
        $message = $this->sha1Message->withoutHeader('Signature');

        $this->assertTrue($this->verifier->isValid($this->sha1Message));

        $message = $this->sha256Message->withHeader(
          'Authorization',
          "Signature {$this->sha256Message->getHeader('Signature')[0]}");
        $message = $this->sha256Message->withoutHeader('Signature');

        $this->assertTrue($this->verifier->isValid($this->sha256Message));
    }

    public function testRejectTamperedRsaRequestMethod()
    {
        $message = $this->sha1Message->withMethod('POST');
        $this->assertFalse($this->verifier->isValid($message));
        $message = $this->sha256Message->withMethod('POST');
        $this->assertFalse($this->verifier->isValid($message));
    }

    public function testRejectTamperedRsaDate()
    {
        $message = $this->sha1Message->withHeader('Date', self::DATE_DIFFERENT);
        $this->assertFalse($this->verifier->isValid($message));
        $message = $this->sha256Message->withHeader('Date', self::DATE_DIFFERENT);
        $this->assertFalse($this->verifier->isValid($message));
    }

    public function testRejectTamperedRsaSignature()
    {
        $message = $this->sha1Message->withHeader(
            'Signature',
            preg_replace('/signature="/', 'signature="x', $this->sha1Message->getHeader('Signature')[0])
        );
        $this->assertFalse($this->verifier->isValid($message));
        $message = $this->sha256Message->withHeader(
            'Signature',
            preg_replace('/signature="/', 'signature="x', $this->sha256Message->getHeader('Signature')[0])
        );
        $this->assertFalse($this->verifier->isValid($message));
    }

    public function testRejectRsaMessageWithoutSignatureHeader()
    {
        $message = $this->sha1Message->withoutHeader('Signature');
        $this->assertFalse($this->verifier->isValid($message));
        $message = $this->sha256Message->withoutHeader('Signature');
        $this->assertFalse($this->verifier->isValid($message));
    }

    public function testRejectRsaMessageWithGarbageSignatureHeader()
    {
        $message = $this->sha1Message->withHeader('Signature', 'not="a",valid="signature"');
        $this->assertFalse($this->verifier->isValid($message));
        $message = $this->sha256Message->withHeader('Signature', 'not="a",valid="signature"');
        $this->assertFalse($this->verifier->isValid($message));
    }

    public function testRejectRsaMessageWithPartialSignatureHeader()
    {
        $message = $this->sha1Message->withHeader('Signature', 'keyId="aa",algorithm="bb"');
        $this->assertFalse($this->verifier->isValid($message));
        $message = $this->sha256Message->withHeader('Signature', 'keyId="aa",algorithm="bb"');
        $this->assertFalse($this->verifier->isValid($message));
    }

    public function testRejectsRsaMessageWithUnknownKeyId()
    {
        $keyStore = new KeyStore(['nope' => 'secret']);
        $verifier = new Verifier($keyStore);
        $this->assertFalse($verifier->isValid($this->sha1Message));
        $this->assertFalse($verifier->isValid($this->sha256Message));
    }

    public function testRejectsRsaMessageMissingSignedHeaders()
    {
        $message = $this->sha1Message->withoutHeader('Date');
        $this->assertFalse($this->verifier->isValid($message));
        $message = $this->sha256Message->withoutHeader('Date');
        $this->assertFalse($this->verifier->isValid($message));
    }
}