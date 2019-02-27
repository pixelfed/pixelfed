<?php

namespace Tests\Unit\Lexer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;

class StatusLexerTest extends TestCase
{
    public $status;
    public $entities;
	public $autolink;

    public function setUp(): void
    {
        parent::setUp();
    	$this->status = "@pixelfed hi, really like the website! #píxelfed";
    	$this->entities = Extractor::create()->extract($this->status);
    	$this->autolink = Autolink::create()->autolink($this->status);
    }

    public function testLexerExtractor()
    {
        $expected = [
            "hashtags" => [
                 "píxelfed",
             ],
             "urls" => [],
             "mentions" => [
                 "pixelfed",
             ],
             "replyto" => "pixelfed",
             "hashtags_with_indices" => [
                 [
                   "hashtag" => "píxelfed",
                   "indices" => [
                         39,
                         48,
                     ],
                 ],
             ],
             "urls_with_indices" => [],
             "mentions_with_indices" => [
                 [
                   "screen_name" => "pixelfed",
                   "indices" => [
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
        $expected = '@<a class="u-url mention" href="https://pixelfed.dev/pixelfed" rel="external nofollow noopener" target="_blank">pixelfed</a> hi, really like the website! <a href="https://pixelfed.dev/discover/tags/píxelfed?src=hash" title="#píxelfed" class="u-url hashtag" rel="external nofollow noopener">#píxelfed</a>';
        $this->assertEquals($this->autolink, $expected);
    }
}
