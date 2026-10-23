<?php

namespace Tests\Unit;

use App\Util\Lexer\Nickname;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebfingerTest extends TestCase
{
    #[Test]
    public function webfinger_test()
    {
        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup',
        ];
        $actual = Nickname::normalizeProfileUrl('acct:dansup@pixelfed.org');
        $this->assertEquals($expected, $actual);

        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup_',
        ];
        $actual = Nickname::normalizeProfileUrl('acct:dansup@pixelfed.org');
        $this->assertNotEquals($expected, $actual);

        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup',
        ];
        $actual = Nickname::normalizeProfileUrl('acct:@dansup@pixelfed.org');
        $this->assertEquals($expected, $actual);

        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup',
        ];
        $actual = Nickname::normalizeProfileUrl('dansup@pixelfed.org');
        $this->assertEquals($expected, $actual);

        $expected = [
            'domain' => 'pixelfed.org',
            'username' => 'dansup',
        ];
        $actual = Nickname::normalizeProfileUrl('@dansup@pixelfed.org');
        $this->assertEquals($expected, $actual);
    }
}
