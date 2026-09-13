<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension\Functions;

use CAMOO\Exception\Exception;
use CAMOO\Http\ServerRequest;
use CAMOO\Interfaces\TemplateFunctionInterface;
use InvalidArgumentException;
use Twig\TwigFunction;

/**
 * Class Html
 *
 * @author CamooSarl
 */
final class Html implements TemplateFunctionInterface
{
    private array $css = [];

    private array $script = [];

    public function __construct(private readonly ServerRequest $request)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('html_script', $this->addJs(...)),
            new TwigFunction('html_css', $this->addCss(...)),
            new TwigFunction('html_fetch', $this->fetch(...), ['is_safe' => ['html']]),
        ];
    }

    public function addJs(string $js): void
    {
        $jsExploded = explode('.', $js);
        $extension = end($jsExploded);
        if (strtolower($extension) !== 'js') {
            $js = $js . '.js';
        }
        $this->assertSafeAsset($js, 'js');
        $this->script[] = sprintf('<script src="/js/%s"></script>' . "\n", $js);
    }

    public function addCss(string $css): void
    {
        $cssExploded = explode('.', $css);
        $extension = end($cssExploded);
        if (strtolower($extension) !== 'css') {
            $css = $css . '.css';
        }
        $this->assertSafeAsset($css, 'css');

        $this->css[] = sprintf('<link rel="stylesheet" href="/css/%s">' . "\n", $css);
    }

    public function fetch(string $item): string
    {
        if (!in_array($item, ['script', 'css'])) {
            throw new Exception(sprintf('Item %s is not allowed !', $item));
        }

        $asItems = $this->{$item};

        return implode('', $asItems);
    }

    private function assertSafeAsset(string $asset, string $extension): void
    {
        $pattern = sprintf(
            '/\\A(?:[A-Za-z0-9_-]+\\/)*[A-Za-z0-9_-]+(?:\\.[A-Za-z0-9_-]+)*\\.%s\\z/i',
            preg_quote($extension, '/'),
        );
        if (!preg_match($pattern, $asset)) {
            throw new InvalidArgumentException(sprintf('Invalid %s asset name.', $extension));
        }
    }
}
