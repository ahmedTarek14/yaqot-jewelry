<?php

namespace Tests\Unit;

use App\Support\PlainText;
use PHPUnit\Framework\TestCase;

class PlainTextTest extends TestCase
{
    public function test_blank_lines_separate_paragraphs(): void
    {
        $this->assertSame(
            ['First line', "Second paragraph\nwith a line break", 'Third'],
            PlainText::paragraphs("  First line\n\nSecond paragraph\nwith a line break\r\n \r\n\n\nThird  "),
        );
    }

    public function test_empty_text_has_no_paragraphs(): void
    {
        $this->assertSame([], PlainText::paragraphs(null));
        $this->assertSame([], PlainText::paragraphs(''));
        $this->assertSame([], PlainText::paragraphs("  \n\n  "));
    }

    public function test_markup_is_kept_as_text(): void
    {
        $this->assertSame(['<b>bold</b>'], PlainText::paragraphs('<b>bold</b>'));
    }
}
