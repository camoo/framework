<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Template\Extension;

use CAMOO\Exception\Exception;
use CAMOO\Http\ServerRequest;
use CAMOO\Template\Extension\Functions\Html;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Html::class)]
class HtmlTest extends TestCase
{
    private Html $html;

    public function setUp(): void
    {
        $this->html = new Html(new ServerRequest());
    }

    public function testGetFunctions(): void
    {
        $funcs = $this->html->getFunctions();
        $this->assertCount(3, $funcs);
    }

    public function testAddJsAndCssAndFetch(): void
    {
        $this->html->addJs('app');
        $this->html->addJs('main.js');
        $this->html->addCss('style');
        $this->html->addCss('custom.css');

        $js = $this->html->fetch('script');
        $this->assertStringContainsString('/js/app.js', $js);
        $this->assertStringContainsString('/js/main.js', $js);

        $css = $this->html->fetch('css');
        $this->assertStringContainsString('/css/style.css', $css);
        $this->assertStringContainsString('/css/custom.css', $css);
    }

    public function testFetchInvalidThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->html->fetch('invalid');
    }
}
