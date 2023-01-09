<?php

namespace Tests\Unit\Lexer;

use App\Status;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
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

    public function testLexerExtractor()
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

    public function testAutolink()
    {
        $expected = '<a class="u-url mention" href="https://pixelfed.dev/pixelfed" rel="external nofollow noopener" target="_blank">@pixelfed</a> hi, really like the website! <a href="https://pixelfed.dev/discover/tags/píxelfed?src=hash" title="#píxelfed" class="u-url hashtag" rel="external nofollow noopener">#píxelfed</a>';
        $this->assertEquals($this->autolink, $expected);
    }

    /** @test * */
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

    /** @test * */
    public function mentionLimit()
    {
        $text = '@test1 @test @test2 @test3 @test4 @test5 test post';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['mentions']);
        $this->assertEquals($count, Status::MAX_MENTIONS);
    }

    /** @test * */
    public function hashtagLimit()
    {
        $text = '#hashtag0 #hashtag1 #hashtag2 #hashtag3 #hashtag4 #hashtag5 #hashtag6 #hashtag7 #hashtag8 #hashtag9 #hashtag10 #hashtag11 #hashtag12 #hashtag13 #hashtag14 #hashtag15 #hashtag16 #hashtag17 #hashtag18 #hashtag19 #hashtag20 #hashtag21 #hashtag22 #hashtag23 #hashtag24 #hashtag25 #hashtag26 #hashtag27 #hashtag28 #hashtag29 #hashtag30 #hashtag31';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['hashtags']);
        $this->assertEquals($count, Status::MAX_HASHTAGS);
    }


    /** @test * */
    public function linkLimit()
    {
        $text = 'https://example.org https://example.net https://example.com';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['urls']);
        $this->assertEquals($count, Status::MAX_LINKS);
    }
}
