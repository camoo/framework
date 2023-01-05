<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension\Filters;

use CAMOO\Http\ServerRequest;
use CAMOO\Interfaces\TemplateFilterInterface;
use Twig\TwigFilter;

/**
 * Class Flash
 *
 * @author CamooSarl
 */
final class Flash implements TemplateFilterInterface
{
    private ?\CAMOO\Http\Flash $flash;

    private ServerRequest $request;

    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
        $this->flash = $this->request->Flash;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('show_flash', [$this, 'display'], ['is_safe' => ['html']]),
        ];
    }

    public function display(string $key): ?string
    {
        $flash = $this->request->getSession()->read('CAMOO.SYS.FLASH');
        if (empty($flash)) {
            return null;
        }
        $asFlash = [];
        $message = $this->flash->get($key);
        if (null === $message) {
            return null;
        }
        foreach ($flash as $keyContainer => $alert) {
            if (null === $alert) {
                continue;
            }
            if ($key === $keyContainer) {
                if (method_exists($this, $alert)) {
                    $asFlash[] = $this->{$alert}($this->flash->get($key));
                } else {
                    $asFlash[] = $this->default($this->flash->get($key));
                }
            }
        }

        return count($asFlash) > 0 ? implode('', $asFlash) : null;
    }

    private function success(string $message): string
    {
        return $this->buildAlert('success', $message);
    }

    private function info(string $message): string
    {
        return $this->buildAlert('info', $message);
    }

    private function warning(string $message): string
    {
        return $this->buildAlert('warning', $message);
    }

    private function error(string $message): string
    {
        return $this->buildAlert('danger', $message);
    }

    private function default(string $message): string
    {
        return $this->buildAlert('secondary', $message);
    }

    private function buildAlert(string $alert, string $message): string
    {
        $custom = sprintf('%sTemplate/Layouts/Alerts/%s.ctpl', APP, $alert);
        if (is_file($custom) && ($content = file_get_contents($custom))) {
            return str_replace('#message#', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $content);
        }

        return '<div class="alert alert-' . $alert . ' alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>' .
                        htmlspecialchars($message, ENT_QUOTES, 'UTF-8') .
            '</div>';
    }
}
