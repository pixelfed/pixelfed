<?php

namespace Tests\Unit\HttpSignatures;

use GuzzleHttp\Psr7\Request;
use App\Util\HttpSignatures\HeaderList;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use App\Util\HttpSignatures\SigningString;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class SigningStringTest extends \PHPUnit\Framework\TestCase
{
    public function testWithoutQueryString()
    {
        $headerList = new HeaderList(['(request-target)']);
        $ss = new SigningString($headerList, $this->message('/path'));

        $this->assertEquals(
            '(request-target): get /path',
            $ss->string()
        );
    }

    public function testSigningStringWithOrderedQueryParameters()
    {
        $headerList = new HeaderList(['(request-target)', 'date']);
        $ss = new SigningString($headerList, $this->message('/path?a=antelope&z=zebra'));

        $this->assertEquals(
            "(request-target): get /path?a=antelope&z=zebra\ndate: Mon, 28 Jul 2014 15:39:13 -0700",
            $ss->string()
        );
    }

    public function testSigningStringWithUnorderedQueryParameters()
    {
        $headerList = new HeaderList(['(request-target)', 'date']);
        $ss = new SigningString($headerList, $this->message('/path?z=zebra&a=antelope'));

        $this->assertEquals(
            "(request-target): get /path?z=zebra&a=antelope\ndate: Mon, 28 Jul 2014 15:39:13 -0700",
            $ss->string()
        );
    }

    public function testSigningStringWithOrderedQueryParametersSymfonyRequest()
    {
        $headerList = new HeaderList(['(request-target)', 'date']);
        $ss = new SigningString($headerList, $this->symfonyMessage('/path?a=antelope&z=zebra'));

        $this->assertEquals(
            "(request-target): get /path?a=antelope&z=zebra\ndate: Mon, 28 Jul 2014 15:39:13 -0700",
            $ss->string()
        );
    }

    public function testSigningStringWithUnorderedQueryParametersSymfonyRequest()
    {
        $headerList = new HeaderList(['(request-target)', 'date']);
        $ss = new SigningString($headerList, $this->symfonyMessage('/path?z=zebra&a=antelope'));

        $this->assertEquals(
            "(request-target): get /path?z=zebra&a=antelope\ndate: Mon, 28 Jul 2014 15:39:13 -0700",
            $ss->string()
        );
    }

    /**
     * @expectedException App\Util\HttpSignatures\Exception
     */
    public function testSigningStringErrorForMissingHeader()
    {
        $headerList = new HeaderList(['nope']);
        $ss = new SigningString($headerList, $this->message('/'));
        $ss->string();
    }

    private function message($path)
    {
        return new Request('GET', $path, ['date' => 'Mon, 28 Jul 2014 15:39:13 -0700']);
    }

    private function symfonyMessage($path)
    {
        $symfonyRequest = SymfonyRequest::create($path, 'GET');
        $symfonyRequest->headers->replace(['date' => 'Mon, 28 Jul 2014 15:39:13 -0700']);

        $psr7Factory = new DiactorosFactory();
        $psrRequest = $psr7Factory->createRequest($symfonyRequest)->withRequestTarget($symfonyRequest->getRequestUri());

        return $psrRequest;
    }
}
