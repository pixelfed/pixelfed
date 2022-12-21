<?php

namespace Tests\Unit;

use App\Util\Lexer\Bearcap;
use PHPUnit\Framework\TestCase;

class BearcapTest extends TestCase
{
    /** @test */
    public function validTest()
    {
        $str = 'bear:?t=LpVypnEUdHhwwgXE9tTqEwrtPvmLjqYaPexqyXnVo1flSfJy5AYMCdRPiFRmqld2&u=https://pixelfed.test/stories/admin/337892163734081536';
        $expected = [
            'token' => 'LpVypnEUdHhwwgXE9tTqEwrtPvmLjqYaPexqyXnVo1flSfJy5AYMCdRPiFRmqld2',
            'url' => 'https://pixelfed.test/stories/admin/337892163734081536',
        ];
        $actual = Bearcap::decode($str);
        $this->assertEquals($expected, $actual);
    }

    /** @test */
    public function invalidTokenParameterName()
    {
        $str = 'bear:?token=LpVypnEUdHhwwgXE9tTqEwrtPvmLjqYaPexqyXnVo1flSfJy5AYMCdRPiFRmqld2&u=https://pixelfed.test/stories/admin/337892163734081536';
        $actual = Bearcap::decode($str);
        $this->assertFalse($actual);
    }

    /** @test */
    public function invalidUrlParameterName()
    {
        $str = 'bear:?t=LpVypnEUdHhwwgXE9tTqEwrtPvmLjqYaPexqyXnVo1flSfJy5AYMCdRPiFRmqld2&url=https://pixelfed.test/stories/admin/337892163734081536';
        $actual = Bearcap::decode($str);
        $this->assertFalse($actual);
    }

    /** @test */
    public function invalidScheme()
    {
        $str = 'bearcap:?t=LpVypnEUdHhwwgXE9tTqEwrtPvmLjqYaPexqyXnVo1flSfJy5AYMCdRPiFRmqld2&url=https://pixelfed.test/stories/admin/337892163734081536';
        $actual = Bearcap::decode($str);
        $this->assertFalse($actual);
    }

    /** @test */
    public function missingToken()
    {
        $str = 'bear:?u=https://pixelfed.test/stories/admin/337892163734081536';
        $actual = Bearcap::decode($str);
        $this->assertFalse($actual);
    }

    /** @test */
    public function missingUrl()
    {
        $str = 'bear:?t=LpVypnEUdHhwwgXE9tTqEwrtPvmLjqYaPexqyXnVo1flSfJy5AYMCdRPiFRmqld2';
        $actual = Bearcap::decode($str);
        $this->assertFalse($actual);
    }

    /** @test */
    public function invalidHttpUrl()
    {
        $str = 'bear:?t=LpVypnEUdHhwwgXE9tTqEwrtPvmLjqYaPexqyXnVo1flSfJy5AYMCdRPiFRmqld2&u=http://pixelfed.test/stories/admin/337892163734081536';
        $actual = Bearcap::decode($str);
        $this->assertFalse($actual);
    }

    /** @test */
    public function invalidUrlSchema()
    {
        $str = 'bear:?t=LpVypnEUdHhwwgXE9tTqEwrtPvmLjqYaPexqyXnVo1flSfJy5AYMCdRPiFRmqld2&u=phar://pixelfed.test/stories/admin/337892163734081536';
        $actual = Bearcap::decode($str);
        $this->assertFalse($actual);
    }
}
