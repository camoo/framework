<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Utils;

use CAMOO\Utils\Security;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Security::class)]
class SecurityTest extends TestCase
{
    public function testSatanizer(): void
    {
        $input = "  <script>alert('xss')</script> Hello World! %20  ";
        $clean = Security::satanizer($input);
        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringContainsString('Hello World!', $clean);

        $cleanWithNewlines = Security::satanizer("Line 1\nLine 2", true);
        $this->assertStringContainsString("\n", $cleanWithNewlines);
    }

    public function testSatanizerInvalidEncoding(): void
    {
        $invalidUtf8 = "\x80\x81";
        $this->assertSame('', Security::satanizer($invalidUtf8));
    }

    public function testStripAllTags(): void
    {
        $html = '<style>body{color:red;}</style><h1>Title</h1><p>Paragraph</p>';
        $stripped = Security::stripAllTags($html, true);
        $this->assertSame('TitleParagraph', $stripped);
    }
}
