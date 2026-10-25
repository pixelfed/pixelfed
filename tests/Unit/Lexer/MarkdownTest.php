<?php

namespace Tests\Unit\Lexer;

use App\Util\Lexer\Autolink;
use App\Util\Lexer\Markdown;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkdownTest extends TestCase
{
    public static function captions(): array
    {
        return [
            'bold' => ['**bold** text', '<strong>bold</strong> text'],
            'italic' => ['some *italic* text', 'some <em>italic</em> text'],
            'several spans' => ['**a** *b* **c**', '<strong>a</strong> <em>b</em> <strong>c</strong>'],
            'italic inside bold' => ['**bold *nested* bold**', '<strong>bold <em>nested</em> bold</strong>'],
            'bold inside italic' => ['*a **b** c*', '<em>a <strong>b</strong> c</em>'],
            'misnested is left alone' => ['*a **b* c**', '*a <strong>b* c</strong>'],
            'spaced asterisks' => ['a * b * c and ** d **', 'a * b * c and ** d **'],
            'intraword asterisks' => ['2*3*4', '2*3*4'],
            'unclosed' => ['**open and *open', '**open and *open'],
            'no span across lines' => ["**one\ntwo**", "**one\ntwo**"],
            'underscores untouched' => ['snake_case_word and __x__', 'snake_case_word and __x__'],
        ];
    }

    #[Test]
    #[DataProvider('captions')]
    public function renders_inline_markdown(string $caption, string $expected)
    {
        $this->assertEquals($expected, Markdown::inline(Autolink::create()->autolink($caption)));
    }

    #[Test]
    public function wraps_whole_hashtag_links()
    {
        $actual = Markdown::inline(Autolink::create()->autolink('**#pixelfed** rocks'));

        $this->assertStringStartsWith('<strong><a href=', $actual);
        $this->assertStringEndsWith('>#pixelfed</a></strong> rocks', $actual);
    }

    #[Test]
    public function ignores_asterisks_inside_links()
    {
        $html = Autolink::create()->autolink('see https://example.org/a*b*c and *this*');
        $actual = Markdown::inline($html);

        $this->assertStringContainsString('href="https://example.org/a*b*c"', $actual);
        $this->assertStringContainsString('>https://example.org/a*b*c</a>', $actual);
        $this->assertStringEndsWith(' and <em>this</em>', $actual);
    }
}
