<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension\Functions;

use \CAMOO\Utils\Security;
use Twig\TwigFunction;
use CAMOO\Http\ServerRequest;
use CAMOO\Interfaces\TemplateFunctionInterface;
use CAMOO\Http\SessionSegment;

/**
 * Class Form
 * @author CamooSarl
 */
final class Form implements TemplateFunctionInterface
{
    /** @var ServerRequest $request */
    private $request;

    /** @var SessionSegment $csrfSessionSegment */
    private $csrfSessionSegment = null;

    public function __construct(ServerRequest $request, SessionSegment $csrfSessionSegment)
    {
        $this->request = $request;
        $this->csrfSessionSegment = $csrfSessionSegment;
    }

	/** @var array */
    private $hiddenValue = [];

    public function getFunctions() : array
    {
        return [
            new TwigFunction('form_start', [$this, 'formStart']),
            new TwigFunction('form_end', [$this, 'formEnd']),
            new TwigFunction('form_input', [$this, 'input']),
        ];
    }

    private function _buildAttr(array $options)
    {
        $attributes = ' ';
        foreach ($options as $attr => $option) {
            $attributes .= $attr. '="' . $option. '" ';
        }
        return $attributes;
    }

    public function formStart(?string $name=null, ?string $method='POST', string $action='', $options=[])
    {
        $token = $this->request->getCsrfToken();
        $name = $name ?? uniqid('form', false);
        $default= [ 'id' => $name];
        $options += $default;
        return sprintf('<form name="%s" method="%s" action="%s"%s>' ."\n".' <input type="hidden" name="__csrf_Token" value="'.$token.'" />', $name, $method, $action, $this->_buildAttr($options));
    }

    public function formEnd()
    {
        return '</form>';
    }

    public function input(string $name, array $options=[], $template=null)
    {
        $default= [ 'id' => $name, 'type' => 'text'];
        $options += $default;
        if (array_key_exists('type', $options) && $options['type'] === 'textarea') {
            unset($options['type']);
            return sprintf('<textarea name="%s"%s></textarea>', $name, rtrim($this->_buildAttr($options)));
        }

        if (array_key_exists('type', $options) && strtolower($options['type']) !== 'email' && $name === 'email') {
            $options['type'] = 'email';
        }

        if (array_key_exists('type', $options) && strtolower($options['type']) !== 'password' && $name === 'password') {
            $options['type'] = 'password';
        }

        if (array_key_exists('type', $options) && strtolower($options['type']) === 'hidden') {
            $this->hiddenValue[$name] = md5(Security::satanizer((string)$options['value']));
            $this->request->csrfSessionSegment->write('__csrf_checksum', $this->hiddenValue);
        }

        return sprintf('<input name="%s"%s />', $name, rtrim($this->_buildAttr($options)));
    }
}
