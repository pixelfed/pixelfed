<?php

namespace Tests\Unit\Lexer;

use App\Models\Status;
use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusLexerTest extends TestCase
{
    public $status;

    public $entities;

    public $autolink;

    protected function setUp(): void
    {
        parent::setUp();
        $this->status = '@pixelfed hi, really like the website! #píxelfed';
        $this->entities = Extractor::create()->extract($this->status);
        $this->autolink = Autolink::create()->autolink($this->status);
    }

    #[Test]
    public function lexer_extractor()
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
                ],
            ],
        ];

        $this->assertEquals($this->entities, $expected);
    }

    #[Test]
    public function autolink()
    {
        $expected = '<a class="u-url mention" href="'.config('app.url').'/pixelfed" rel="external nofollow noopener" target="_blank">@pixelfed</a> hi, really like the website! <a href="'.config('app.url').'/discover/tags/píxelfed?src=hash" title="#píxelfed" class="u-url hashtag" rel="external nofollow noopener">#píxelfed</a>';
        $this->assertEquals($this->autolink, $expected);
    }

    #[Test]
    public function remote_mention()
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
    public function remote_mention_with_long_username(): void
    {
        // A remote handle whose local part is longer than the old 20-char cap
        // (23 chars here) must still be extracted and autolinked, the same way
        // a short remote handle is (#7204).
        $handle = '@stadtlandfluchtfestival@pixelfed.de';

        $entities = Extractor::create()->extract('hello '.$handle);

        $this->assertContains($handle, $entities['mentions']);

        // It is turned into an anchor rather than left as plain text.
        $autolink = Autolink::create()->autolink('hello '.$handle);
        $this->assertStringContainsString('<a ', $autolink);
        $this->assertStringContainsString('>'.$handle.'</a>', $autolink);
    }

    #[Test]
    public function remote_mention_username_over_thirty_keeps_domain(): void
    {
        // A remote username longer than the local 30-char limit must not be
        // truncated with its @domain dropped (which turned a remote mention
        // into a broken local one). The full handle must be captured (#7204).
        $handle = '@aReallyLongRemoteUsernameThatExceedsThirty@example.social';

        $entities = Extractor::create()->extract('hi '.$handle);

        // Mentions are lowercased by the extractor.
        $this->assertContains(mb_strtolower($handle), $entities['mentions']);

        // The domain is preserved (not dropped by a too-small local-part cap).
        foreach ($entities['mentions'] as $mention) {
            $this->assertStringContainsString('@example.social', $mention);
        }
    }

    #[Test]
    public function mention_limit()
    {
        $text = '@test1 @test @test2 @test3 @test4 @test5 @test6 @test7 @test8 @test9 @test10 @test11 @test12 @test13 @test14 @test15 @test16 @test17 @test18 @test19 test post';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['mentions']);
        $this->assertEquals(Status::MAX_MENTIONS, $count);
    }

    #[Test]
    public function hashtag_limit()
    {
        $text = '#hashtag0 #hashtag1 #hashtag2 #hashtag3 #hashtag4 #hashtag5 #hashtag6 #hashtag7 #hashtag8 #hashtag9 #hashtag10 #hashtag11 #hashtag12 #hashtag13 #hashtag14 #hashtag15 #hashtag16 #hashtag17 #hashtag18 #hashtag19 #hashtag20 #hashtag21 #hashtag22 #hashtag23 #hashtag24 #hashtag25 #hashtag26 #hashtag27 #hashtag28 #hashtag29 #hashtag30 #hashtag31 #hashtag0 #hashtag1 #hashtag2 #hashtag3 #hashtag4 #hashtag5 #hashtag6 #hashtag7 #hashtag8 #hashtag9 #hashtag10 #hashtag11 #hashtag12 #hashtag13 #hashtag14 #hashtag15 #hashtag16 #hashtag17 #hashtag18 #hashtag19 #hashtag20 #hashtag21 #hashtag22 #hashtag23 #hashtag24 #hashtag25 #hashtag26 #hashtag27 #hashtag28 #hashtag29 #hashtag30 #hashtag31';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['hashtags']);
        $this->assertEquals(Status::MAX_HASHTAGS, $count);
    }

    #[Test]
    public function link_limit()
    {
        $text = 'https://example.org https://example.net https://example.com https://example.com https://example.net';

        $entities = Extractor::create()->extract($text);
        $count = count($entities['urls']);
        $this->assertEquals(Status::MAX_LINKS, $count);
    }

    /*
    |--------------------------------------------------------------------------
    | Local mention extraction
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function extracts_a_simple_local_mention(): void
    {
        $entities = Extractor::create()->extract('hey @dansup how are you');

        $this->assertEquals(['dansup'], $entities['mentions']);
    }

    #[Test]
    public function extracts_multiple_local_mentions(): void
    {
        $entities = Extractor::create()->extract('@alice @bob @carol');

        $this->assertEquals(['alice', 'bob', 'carol'], $entities['mentions']);
    }

    #[Test]
    public function deduplicates_repeated_mentions(): void
    {
        $entities = Extractor::create()->extract('@alice @alice @alice');

        $this->assertEquals(['alice'], $entities['mentions']);
    }

    #[Test]
    public function lowercases_extracted_mentions(): void
    {
        $entities = Extractor::create()->extract('@DanSup @HELLO');

        $this->assertEquals(['dansup', 'hello'], $entities['mentions']);
    }

    #[Test]
    public function extracts_mention_with_underscores_dashes_and_dots(): void
    {
        $entities = Extractor::create()->extract('@a_b-c.d');

        $this->assertEquals(['a_b-c.d'], $entities['mentions']);
    }

    #[Test]
    public function extracts_mention_with_unicode_letters(): void
    {
        // The local-part class includes \p{L}, so accented usernames match.
        $entities = Extractor::create()->extract('@josé');

        $this->assertEquals(['josé'], $entities['mentions']);
    }

    #[Test]
    public function does_not_extract_bare_at_sign(): void
    {
        $entities = Extractor::create()->extract('email me @ home');

        $this->assertEmpty($entities['mentions']);
    }

    #[Test]
    public function does_not_treat_email_address_as_mention(): void
    {
        // Preceded by a word character, so the @ is not a mention boundary.
        $entities = Extractor::create()->extract('contact test@example.com please');

        $this->assertEmpty($entities['mentions']);
    }

    /*
    |--------------------------------------------------------------------------
    | Local-part length boundaries (#7204)
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function extracts_mention_at_thirty_chars(): void
    {
        $name = str_repeat('a', 30);

        $entities = Extractor::create()->extract('@'.$name);

        $this->assertEquals([$name], $entities['mentions']);
    }

    #[Test]
    public function extracts_local_mention_between_thirty_and_sixty_four_chars(): void
    {
        // Previously (cap 30) this would have been truncated to 30 chars.
        $name = str_repeat('a', 45);

        $entities = Extractor::create()->extract('@'.$name);

        $this->assertEquals([$name], $entities['mentions']);
    }

    #[Test]
    public function extracts_mention_at_sixty_four_chars(): void
    {
        $name = str_repeat('a', 64);

        $entities = Extractor::create()->extract('@'.$name);

        $this->assertEquals([$name], $entities['mentions']);
    }

    #[Test]
    public function truncates_local_part_beyond_sixty_four_chars(): void
    {
        // Beyond the cap the extra characters are not part of the handle.
        $name = str_repeat('a', 70);

        $entities = Extractor::create()->extract('@'.$name);

        $this->assertEquals([str_repeat('a', 64)], $entities['mentions']);
    }

    /*
    |--------------------------------------------------------------------------
    | Remote mention extraction (@user@domain)
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function extracts_a_short_remote_mention(): void
    {
        $entities = Extractor::create()->extract('cc @shlee@aus.social');

        $this->assertContains('@shlee@aus.social', $entities['mentions']);
    }

    #[Test]
    public function extracts_remote_mention_with_multi_label_domain(): void
    {
        $handle = '@user@sub.domain.example.co.uk';

        $entities = Extractor::create()->extract('hi '.$handle);

        $this->assertContains($handle, $entities['mentions']);
    }

    #[Test]
    public function extracts_remote_mention_with_very_long_domain(): void
    {
        // The domain is not counted against the local-part cap.
        $handle = '@user@averylongsubdomain.example.social.network.example.org';

        $entities = Extractor::create()->extract('hi '.$handle);

        $this->assertContains($handle, $entities['mentions']);
    }

    #[Test]
    public function extracts_remote_mention_with_dashed_domain(): void
    {
        $handle = '@user@my-instance.example';

        $entities = Extractor::create()->extract('hi '.$handle);

        $this->assertContains($handle, $entities['mentions']);
    }

    #[Test]
    public function extracts_mixed_local_and_remote_mentions(): void
    {
        $entities = Extractor::create()->extract('@localuser and @remote@example.social');

        $this->assertContains('localuser', $entities['mentions']);
        $this->assertContains('@remote@example.social', $entities['mentions']);
    }

    /*
    |--------------------------------------------------------------------------
    | Autolink output
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function autolinks_a_local_mention_with_mention_class(): void
    {
        $html = Autolink::create()->autolink('hi @dansup');

        $this->assertStringContainsString('class="u-url mention"', $html);
        $this->assertStringContainsString('>@dansup</a>', $html);
        $this->assertStringContainsString('href="'.config('app.url').'/dansup"', $html);
    }

    #[Test]
    public function autolinks_a_remote_mention_into_an_anchor(): void
    {
        $handle = '@shlee@aus.social';

        $html = Autolink::create()->autolink('hi '.$handle);

        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('>'.$handle.'</a>', $html);
    }

    #[Test]
    public function autolinks_a_long_remote_mention_the_same_as_a_short_one(): void
    {
        $short = Autolink::create()->autolink('@shlee@aus.social');
        $long = Autolink::create()->autolink('@stadtlandfluchtfestival@pixelfed.de');

        // Both remote handles produce an anchor carrying the full handle.
        $this->assertStringContainsString('>@shlee@aus.social</a>', $short);
        $this->assertStringContainsString('>@stadtlandfluchtfestival@pixelfed.de</a>', $long);
    }

    #[Test]
    public function leaves_text_without_mentions_untouched(): void
    {
        $text = 'just a plain sentence with no entities';

        $this->assertEquals($text, Autolink::create()->autolink($text));
    }

    /*
    |--------------------------------------------------------------------------
    | Reply detection
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function detects_reply_to_a_leading_local_mention(): void
    {
        $entities = Extractor::create()->extract('@dansup hello there');

        $this->assertEquals('dansup', $entities['replyto']);
    }

    #[Test]
    public function detects_reply_for_a_long_leading_username(): void
    {
        // Reply detection shares the same local-part cap as mention detection.
        $name = str_repeat('a', 45);

        $entities = Extractor::create()->extract('@'.$name.' hi');

        $this->assertEquals($name, $entities['replyto']);
    }

    #[Test]
    public function no_reply_when_mention_is_not_leading(): void
    {
        $entities = Extractor::create()->extract('hello @dansup');

        $this->assertNull($entities['replyto']);
    }

    /*
    |--------------------------------------------------------------------------
    | Mentions alongside hashtags and urls
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function extracts_mention_hashtag_and_url_together(): void
    {
        $entities = Extractor::create()->extract('@dansup check #pixelfed at https://pixelfed.org');

        $this->assertEquals(['dansup'], $entities['mentions']);
        $this->assertEquals(['pixelfed'], $entities['hashtags']);
        $this->assertEquals(['https://pixelfed.org'], $entities['urls']);
    }

    #[Test]
    public function mention_immediately_before_punctuation_is_extracted(): void
    {
        $entities = Extractor::create()->extract('thanks @dansup!');

        $this->assertEquals(['dansup'], $entities['mentions']);
    }

    #[Test]
    public function mention_wrapped_in_parentheses_is_extracted(): void
    {
        $entities = Extractor::create()->extract('(via @dansup)');

        $this->assertEquals(['dansup'], $entities['mentions']);
    }
}
