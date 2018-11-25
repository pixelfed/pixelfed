<?php

namespace Tests\Unit\HttpSignatures;

use GuzzleHttp\Psr7\Request;
use App\Util\HttpSignatures\Context;
use Tests\Unit\HttpSignatures\TestKeys;

class RsaContextTest extends \PHPUnit\Framework\TestCase
{
    private $context;

    public function setUp()
    {
        $this->sha1context = new Context([
            'keys' => ['rsa1' => TestKeys::rsaPrivateKey],
            'algorithm' => 'rsa-sha1',
            'headers' => ['(request-target)', 'date'],
        ]);
        $this->sha256context = new Context([
            'keys' => ['rsa1' => TestKeys::rsaPrivateKey],
            'algorithm' => 'rsa-sha256',
            'headers' => ['(request-target)', 'date'],
        ]);
    }

    public function testSha1Signer()
    {
        $message = new Request('GET', '/path?query=123', ['date' => 'today', 'accept' => 'llamas']);

        $message = $this->sha1context->signer()->sign($message);
        $expectedSha1String = implode(',', [
            'keyId="rsa1"',
            'algorithm="rsa-sha1"',
            'headers="(request-target) date"',
            'signature="YIR3DteE3Jmz1VAnUMTgjTn3vTKfQuZl1CJhMBvGOZpnzwKeYBXA'.
              'H108FojnbSeVG/AXq9pcrA6AFK0peg0aueqxpaFlo+4L/q5XzJ+QoryY3dlSr'.
              'xwVnE5s5M19xmFm/6YkZR/KPeANCsG4SPL82Um/PCEMU0tmKd6sSx+IIzAYbX'.
              'G/VrFMDeQAdXqpU1EhgxopKEAapN8rChb49+1JfR/RxlSKiLukJJ6auurm2zM'.
              'n2D40fR1d2umA5LAO7vRt2iQwVbtwiFkVlRqkMvGftCNZByu8jJ6StI5H7Efu'.
              'ANSHAZXKXWNH8yxpBUW/QCHCZjPd0ugM0QJJIc7i8JbGlA=="',
        ]);

        $this->assertEquals(
            $expectedSha1String,
            $message->getHeader('Signature')[0]
        );
    }

    public function testSha256Signer()
    {
        $message = new Request('GET', '/path?query=123', ['date' => 'today', 'accept' => 'llamas']);

        $message = $this->sha256context->signer()->sign($message);
        $expectedSha256String = implode(',', [
            'keyId="rsa1"',
            'algorithm="rsa-sha256"',
            'headers="(request-target) date"',
            'signature="WGIegQCC3GEwxbkuXtq67CAqeDhkwblxAH2uoDx5kfWurhLRA5WB'.
            'FDA/aktsZAjuUoimG1w4CGxSecziER1ez44PBlHP2fCW4ArLgnQgcjkdN2cOf/g'.
            'j0OVL8s2usG4o4tud/+jjF3nxTxLl3HC+erBKsJakwXbw9kt4Cr028BToVfNXsW'.
            'oMFpv0IjcgBH2V41AVlX/mYBMMJAihBCIcpgAcGrrxmG2gkfvSn09wtTttkGHft'.
            'PIp3VpB53zbemlJS9Yw3tmmHr6cvWSXqQy/bTsEOoQJ2REfn5eiyzsJu3GiOpiI'.
            'LK67i/WH9moltJtlfV57TV72cgYtjWa6yqhtFg=="',
        ]);

        $this->assertEquals(
            $expectedSha256String,
            $message->getHeader('Signature')[0]
        );
    }

    /**
     * @expectedException     App\Util\HttpSignatures\AlgorithmException
     */
    public function testRsaBadalgorithm()
    {
        $sha224context = new Context([
              'keys' => ['rsa1' => TestKeys::rsaPrivateKey],
              'algorithm' => 'rsa-sha224',
              'headers' => ['(request-target)', 'date'],
          ]);
    }
}
