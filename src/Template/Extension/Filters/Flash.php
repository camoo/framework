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
    private const INFO = 'info';

    private const SUCCESS = 'success';

    private const ERROR = 'error';

    private const WARNING = 'warning';

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
        if (empty($message)) {
            return null;
        }
        foreach ($flash as $keyContainer => $alert) {
            if (null === $alert) {
                continue;
            }
            if ($key !== $keyContainer) {
                continue;
            }

            $asFlash[] = $this->showMessageByType($alert, $this->flash->get($key));
        }

        return !empty($asFlash) ? implode('', $asFlash) : null;
    }

    private function showMessageByType(string $type, string $message): string
    {
        return match ($type) {
            self::INFO => $this->buildAlert(self::INFO, $message),
            self::SUCCESS => $this->buildAlert(self::SUCCESS, $message),
            self::ERROR => $this->buildAlert('danger', $message),
            self::WARNING => $this->buildAlert(self::WARNING, $message),
            default => $this->buildAlert('secondary', $message)
        };
    }

    private function buildAlert(string $alert, string $message): string
    {
        $custom = sprintf('%sTemplate/Layouts/Alerts/%s.tpl', APP, $alert);
        if (is_file($custom) && ($content = file_get_contents($custom))) {
            return str_replace('#message#', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $content);
        }

        return '<div class="alert alert-' . $alert . ' alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>' .
                        htmlspecialchars($message, ENT_QUOTES, 'UTF-8') .
            '</div>';
    }
}
