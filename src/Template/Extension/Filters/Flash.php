<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension\Filters;

use CAMOO\Interfaces\TemplateFilterInterface;
use Twig\TwigFilter;
use CAMOO\Http\ServerRequest;

/**
 * Class Flash
 * @author CamooSarl
 */
final class Flash implements TemplateFilterInterface
{

    /** @var \CAMOO\Http\Flash $flash */
    private $flash;

    /** @var ServerRequest $request */
    private $request;


    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
        $this->flash = $this->request->Flash;
    }

    public function getFilters() : array
    {
        return [
            new TwigFilter('show_flash', [$this, 'display'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $key
     * @return void|none|string
     */
    public function display(string $key)
    {
        if ($flash = $this->request->getSession()->read('CAMOO.SYS.FLASH')) {
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

            if (count($asFlash) > 0) {
                return implode('', $asFlash);
            }
        }
    }

    /**
     * @param string $message
     * @return string
     */
    private function success(string $message) : string
    {
        return $this->buildAlert('success', $message);
    }

    /**
     * @param string $message
     * @return string
     */
    private function info(string $message) : string
    {
        return $this->buildAlert('info', $message);
    }

    /**
     * @param string $message
     * @return string
     */
    private function warning(string $message) : string
    {
        return $this->buildAlert('warning', $message);
    }

    /**
     * @param string $message
     * @return string
     */
    private function error(string $message) : string
    {
        return $this->buildAlert('danger', $message);
    }

    /**
     * @param string $message
     * @return string
     */
    private function default(string $message) : string
    {
        return $this->buildAlert('secondary', $message);
    }

    /**
     * @param string $alert
     * @param string $message
     * @return string
     */
    private function buildAlert(string $alert, string $message) : string
    {
        $custom = sprintf('%sTemplate/Layouts/Alerts/%s.ctpl', APP, $alert);
        if (is_file($custom) && ($content = file_get_contents($custom))) {
            return str_replace('#message#', htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $content);
        }

        return '<div class="alert alert-'.$alert.' alert-dismissible">
  <button type="button" class="close" data-dismiss="alert">&times;</button>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</div>';
    }
}
