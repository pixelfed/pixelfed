<?php

namespace Tests\Unit\Stories;

use App\Http\Controllers\Stories\StoryApiV1Controller;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Guards the text-overlay character whitelist used by
 * StoryApiV1Controller::publishNext(). Accepts a wide range of
 * legitimate Unicode (umlauts, accents, CJK, single-codepoint
 * emoji, regional-indicator flags) while rejecting invisible /
 * bidi-spoofing characters.
 */
class StoryTextOverlayPatternTest extends TestCase
{
    public static function acceptedProvider(): array
    {
        return [
            'ascii'            => ['Hello World 123'],
            'german umlaut'    => ['Hallöchen Müller'],
            'accent nfc'       => ['café'],
            'accent nfd'       => ["cafe\u{0301}"],
            'cyrillic'         => ['Привет'],
            'arabic'           => ['مرحبا'],
            'cjk'              => ['你好世界'],
            'single emoji'     => ['Hi 😀'],
            'heart vs16'       => ["I\u{2764}\u{FE0F}NY"],
            'country flag'     => ['Vacation 🇩🇪'],
            'currency symbols' => ['Price: 100 € or $100'],
            'math symbols'     => ['x²+y²=z²'],
            'punctuation'      => ['Hello, world! (test) — "quoted"'],
            'empty string'     => [''],
        ];
    }

    public static function rejectedProvider(): array
    {
        return [
            'zero-width space'   => ["hello\u{200B}admin"],
            'zero-width nojoin'  => ["a\u{200C}b"],
            'zero-width joiner'  => ["a\u{200D}b"],
            'word joiner'        => ["ab\u{2060}cd"],
            'bom / zwnbsp'       => ["\u{FEFF}leading"],
            'bidi rlo'           => ["innocent\u{202E}cixot"],
            'bidi lro'           => ["\u{202D}override"],
            'ltr isolate'        => ["abc\u{2066}xyz\u{2069}"],
            'rtl isolate'        => ["abc\u{2067}xyz\u{2069}"],
            'arabic letter mark' => ["a\u{061C}b"],
            'mongolian vowel sep'=> ["a\u{180E}b"],
            'private use area'   => ["abc\u{E000}"],
            'tag character'      => ["abc\u{E0041}"],
            'nul byte'           => ["abc\x00def"],
            'tab'                => ["a\tb"],
            'newline'            => ["a\nb"],
            'carriage return'    => ["a\rb"],
            'line separator'     => ["a\u{2028}b"],
            'paragraph separator'=> ["a\u{2029}b"],
        ];
    }

    #[Test]
    #[DataProvider('acceptedProvider')]
    public function pattern_accepts_safe_input(string $input): void
    {
        $this->assertSame(
            1,
            preg_match(StoryApiV1Controller::TEXT_OVERLAY_PATTERN, $input),
            'Expected pattern to accept input: '.bin2hex($input)
        );
    }

    #[Test]
    #[DataProvider('rejectedProvider')]
    public function pattern_rejects_invisible_or_bidi_input(string $input): void
    {
        $this->assertSame(
            0,
            preg_match(StoryApiV1Controller::TEXT_OVERLAY_PATTERN, $input),
            'Expected pattern to reject input: '.bin2hex($input)
        );
    }
}
