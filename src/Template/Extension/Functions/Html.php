<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension\Functions;

use CAMOO\Exception\Exception;
use CAMOO\Http\ServerRequest;
use CAMOO\Interfaces\TemplateFunctionInterface;
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

    public function __construct(private ServerRequest $request)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('html_script', [$this, 'addJs']),
            new TwigFunction('html_css', [$this, 'addCss']),
            new TwigFunction('html_fetch', [$this, 'addCss'], ['is_safe' => ['html']]),
        ];
    }

    public function addJs(string $js): void
    {
        $jsExploded = explode('.', $js);
        $extension = end($jsExploded);
        if (strtolower($extension) !== 'js') {
            $js = $js . '.js';
        }
        $this->script[] = sprintf('<script src="/js/%s"></script>' . "\n", $js);
    }

    public function addCss(string $css): void
    {
        $cssExploded = explode('.', $css);
        $extension = end($cssExploded);
        if (strtolower($extension) !== 'css') {
            $css = $css . '.css';
        }

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
}
