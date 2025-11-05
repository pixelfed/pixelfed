<?php

namespace Tests\Unit\Lexer;

use App\Status;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusLexerTest extends TestCase
{
    public $status;
    public $entities;
    public $autolink;

    public function setUp(): void
    {
        parent::setUp();
        $this->status = '@pixelfed hi, really like the website! #píxelfed';
        $this->entities = Extractor::create()->extract($this->status);
        $this->autolink = Autolink::create()->autolink($this->status);
    }

    #[Test]
    public function lexerExtractor()
    {
        $expected = [
            'hashtags' => [
                'píxelfed',
            ],
            'urls' => [],
            'mentions' => [
                'pixelfed',
            ],
            'replyto' => 'pixelfed',
            'hashtags_with_indices' => [
                [
                    'hashtag' => 'píxelfed',
                    'indices' => [
                        39,
                        48,
                    ],
                ],
            ],
            'urls_with_indices' => [],
            'mentions_with_indices' => [
                [
                    'screen_name' => 'pixelfed',
                    'indices' => [
                        0,
                        9,
                    ],
                ]
            ]
        ];

        $this->assertEquals($this->entities, $expected);
    }

    #[Test]
    public function autolink()
    {
        $expected = '<a class="u-url mention" href="' . config('app.url') . '/pixelfed" rel="external nofollow noopener" target="_blank">@pixelfed</a> hi, really like the website! <a href="' . config('app.url') . '/discover/tags/píxelfed?src=hash" title="#píxelfed" class="u-url hashtag" rel="external nofollow noopener">#píxelfed</a>';
        $this->assertEquals($this->autolink, $expected);
    }

    #[Test]
    public function remoteMention()
    {
        $expected = [
            'hashtags' => [
                'dansup',
            ],
            'urls' => [],
            'mentions' => [
                '@dansup@mstdn.io',
                'test',
            ],
            'replyto' => null,
            'hashtags_with_indices' => [
                [
                    'hashtag' => 'dansup',
                    'indices' => [
                        0,
                        7,
                    ],
                ],
            ],
            'urls_with_indices' => [],
            'mentions_with_indices' => [
                [
                    'screen_name' => '@dansup@mstdn.io',
                    'indices' => [
                        8,
                        24,
                    ],
                ],
                [
                    'screen_name' => 'test',
                    'indices' => [
                        25,
                        30,
                    ],
                ],
            ],
        ];
        $actual = Extractor::create()->extract('#dansup @dansup@mstdn.io @test');
        $this->assertEquals($actual, $expected);
    }

    #[Test]
    public function mentionLimit()
    {
        $text = '@test1 @test @test2 @test3 @test4 @test5 @test6 @test7 @test8 @test9 @test10 @test11 @test12 @test13 @test14 @test15 @test16 @test17 @test18 @test19 test post';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['mentions']);
        $this->assertEquals(Status::MAX_MENTIONS, $count);
    }

    #[Test]
    public function hashtagLimit()
    {
        $text = '#hashtag0 #hashtag1 #hashtag2 #hashtag3 #hashtag4 #hashtag5 #hashtag6 #hashtag7 #hashtag8 #hashtag9 #hashtag10 #hashtag11 #hashtag12 #hashtag13 #hashtag14 #hashtag15 #hashtag16 #hashtag17 #hashtag18 #hashtag19 #hashtag20 #hashtag21 #hashtag22 #hashtag23 #hashtag24 #hashtag25 #hashtag26 #hashtag27 #hashtag28 #hashtag29 #hashtag30 #hashtag31 #hashtag0 #hashtag1 #hashtag2 #hashtag3 #hashtag4 #hashtag5 #hashtag6 #hashtag7 #hashtag8 #hashtag9 #hashtag10 #hashtag11 #hashtag12 #hashtag13 #hashtag14 #hashtag15 #hashtag16 #hashtag17 #hashtag18 #hashtag19 #hashtag20 #hashtag21 #hashtag22 #hashtag23 #hashtag24 #hashtag25 #hashtag26 #hashtag27 #hashtag28 #hashtag29 #hashtag30 #hashtag31';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['hashtags']);
        $this->assertEquals(Status::MAX_HASHTAGS, $count);
    }


    #[Test]
    public function linkLimit()
    {
        $text = 'https://example.org https://example.net https://example.com https://example.com https://example.net';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['urls']);
        $this->assertEquals(Status::MAX_LINKS, $count);
    }
}
