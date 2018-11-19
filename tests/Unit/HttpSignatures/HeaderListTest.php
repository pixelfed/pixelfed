<?php

namespace Tests\Unit\HttpSignatures;

use App\Util\HttpSignatures\HeaderList;

class HeaderListTest extends \PHPUnit\Framework\TestCase
{
    public function testToString()
    {
        $hl = new HeaderList(['(request-target)', 'Date']);
        $this->assertEquals('(request-target) date', $hl->string());
    }

    public function testFromStringRoundTripNormalized()
    {
        $hl = HeaderList::fromString('(request-target) Accept');
        $this->assertEquals('(request-target) accept', $hl->string());
    }
}
