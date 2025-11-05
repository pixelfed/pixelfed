<?php

namespace Tests\Unit\Lexer;

use App\Util\Lexer\Autolink;
use App\Util\Lexer\Extractor;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsernameTest extends TestCase
{
    #[Test]
    public function genericUsername()
    {
        $username = '@dansup';
        $entities = Extractor::create()->extract($username);
        $autolink = Autolink::create()->autolink($username);
        $expectedAutolink = '<a class="u-url mention" href="' . config('app.url') . '/dansup" rel="external nofollow noopener" target="_blank">@dansup</a>';
        $expectedEntity = [
            'hashtags' => [],
            'urls' => [],
            'mentions' => [
                'dansup',
            ],
            'replyto' => 'dansup',
            'hashtags_with_indices' => [],
            'urls_with_indices' => [],
            'mentions_with_indices' => [
                [
                    'screen_name' => 'dansup',
                    'indices' => [
                        0,
                        7,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedAutolink, $autolink);
        $this->assertEquals($expectedEntity, $entities);
    }

    #[Test]
    public function usernameWithPeriod()
    {
        $username = '@dansup.two';
        $autolink = Autolink::create()->autolink($username);
        $entities = Extractor::create()->extract($username);
        $expectedAutolink = '<a class="u-url mention" href="' . config('app.url') . '/dansup.two" rel="external nofollow noopener" target="_blank">@dansup.two</a>';
        $expectedEntity = [
            'hashtags' => [],
            'urls' => [],
            'mentions' => [
                'dansup.two',
            ],
            'replyto' => 'dansup.two',
            'hashtags_with_indices' => [],
            'urls_with_indices' => [],
            'mentions_with_indices' => [
                [
                    'screen_name' => 'dansup.two',
                    'indices' => [
                        0,
                        11,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedAutolink, $autolink);
        $this->assertEquals($expectedEntity, $entities);
    }

    #[Test]
    public function usernameWithDash()
    {
        $username = '@dansup-too';
        $autolink = Autolink::create()->autolink($username);
        $entities = Extractor::create()->extract($username);
        $expectedAutolink = '<a class="u-url mention" href="' . config('app.url') . '/dansup-too" rel="external nofollow noopener" target="_blank">@dansup-too</a>';
        $expectedEntity = [
            'hashtags' => [],
            'urls' => [],
            'mentions' => [
                'dansup-too',
            ],
            'replyto' => 'dansup-too',
            'hashtags_with_indices' => [],
            'urls_with_indices' => [],
            'mentions_with_indices' => [
                [
                    'screen_name' => 'dansup-too',
                    'indices' => [
                        0,
                        11,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedAutolink, $autolink);
        $this->assertEquals($expectedEntity, $entities);
    }

    #[Test]
    public function usernameWithUnderscore()
    {
        $username = '@dansup_too';
        $autolink = Autolink::create()->autolink($username);
        $entities = Extractor::create()->extract($username);
        $expectedAutolink = '<a class="u-url mention" href="' . config('app.url') . '/dansup_too" rel="external nofollow noopener" target="_blank">@dansup_too</a>';
        $expectedEntity = [
            'hashtags' => [],
            'urls' => [],
            'mentions' => [
                'dansup_too',
            ],
            'replyto' => 'dansup_too',
            'hashtags_with_indices' => [],
            'urls_with_indices' => [],
            'mentions_with_indices' => [
                [
                    'screen_name' => 'dansup_too',
                    'indices' => [
                        0,
                        11,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedAutolink, $autolink);
        $this->assertEquals($expectedEntity, $entities);
    }

    #[Test]
    public function multipleMentions()
    {
        $text = 'hello @dansup and @pixelfed.team from @username_underscore';
        $autolink = Autolink::create()->autolink($text);
        $entities = Extractor::create()->extract($text);
        $expectedAutolink = 'hello <a class="u-url mention" href="' . config('app.url') . '/dansup" rel="external nofollow noopener" target="_blank">@dansup</a> and <a class="u-url mention" href="' . config('app.url') . '/pixelfed.team" rel="external nofollow noopener" target="_blank">@pixelfed.team</a> from <a class="u-url mention" href="' . config('app.url') . '/username_underscore" rel="external nofollow noopener" target="_blank">@username_underscore</a>';
        $expectedEntity = [
            'hashtags' => [],
            'urls' => [],
            'mentions' => [
                'dansup',
                'pixelfed.team',
                'username_underscore',
            ],
            'replyto' => null,
            'hashtags_with_indices' => [],
            'urls_with_indices' => [],
            'mentions_with_indices' => [
                [
                    'screen_name' => 'dansup',
                    'indices' => [
                        6,
                        13,
                    ],
                ],
                [
                    'screen_name' => 'pixelfed.team',
                    'indices' => [
                        18,
                        32,
                    ],
                ],
                [
                    'screen_name' => 'username_underscore',
                    'indices' => [
                        38,
                        58,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedAutolink, $autolink);
        $this->assertEquals($expectedEntity, $entities);
    }

    #[Test]
    public function germanUmlatsAutolink()
    {
        $mentions = "@März and @königin and @Glück";
        $autolink = Autolink::create()->autolink($mentions);

        $expectedAutolink = '<a class="u-url mention" href="' . config('app.url') . '/März" rel="external nofollow noopener" target="_blank">@März</a> and <a class="u-url mention" href="' . config('app.url') . '/königin" rel="external nofollow noopener" target="_blank">@königin</a> and <a class="u-url mention" href="' . config('app.url') . '/Glück" rel="external nofollow noopener" target="_blank">@Glück</a>';
        $this->assertEquals($expectedAutolink, $autolink);
    }

    #[Test]
    public function germanUmlatsExtractor()
    {
        $mentions = "@März and @königin and @Glück";
        $entities = Extractor::create()->extract($mentions);

        $expectedEntity = [
            "hashtags" => [],
            "urls" => [],
            "mentions" => [
              "märz",
              "königin",
              "glück",
            ],
            "replyto" => null,
            "hashtags_with_indices" => [],
            "urls_with_indices" => [],
            "mentions_with_indices" => [
              [
                "screen_name" => "März",
                "indices" => [
                  0,
                  5,
                ],
              ],
              [
                "screen_name" => "königin",
                "indices" => [
                  10,
                  18,
                ],
              ],
              [
                "screen_name" => "Glück",
                "indices" => [
                  23,
                  29,
                ],
              ],
            ],
        ];
        $this->assertEquals($expectedEntity, $entities);
    }

    #[Test]
    public function germanUmlatsWebfingerAutolink()
    {
        $mentions = "hello @märz@example.org!";
        $autolink = Autolink::create()->autolink($mentions);

        $expectedAutolink = 'hello <a class="u-url list-slug" href="' . config('app.url') . '/@märz@example.org" rel="external nofollow noopener" target="_blank">@märz@example.org</a>!';
        $this->assertEquals($expectedAutolink, $autolink);
    }
}
