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

    /** @var null|string $token */
    private $token;

    /** @var SessionSegment $csrfSessionSegment */
    private $csrfSessionSegment = null;

    public function __construct(ServerRequest $request, SessionSegment $csrfSessionSegment, string $token)
    {
        $this->request = $request;
        $this->csrfSessionSegment = $csrfSessionSegment;
        $this->token = $token;
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
            if (strtolower($attr) === 'value' && !empty($option)) {
                $option = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
            }
            $attributes .= $attr. '="' . $option. '" ';
        }
        return $attributes;
    }

    public function formStart(?string $name=null, $options=[])
    {
        $token = $this->token;
        $name = $name ?? uniqid('form', false);
        $default= [ 'id' => $name, 'method' => 'POST', 'action' => ''];
        if (array_key_exists('url', $options)) {
            $options['action'] = $options['url'];
			unset($options['url']);
        }
        $options += $default;
        return sprintf('<form name="%s"%s>' ."\n".' <input type="hidden" name="__csrf_Token" value="'.$token.'" />', $name, $this->_buildAttr($options));
    }

    public function formEnd()
    {
        return '</form>';
    }

    public function input(string $name, array $options=[], $template=null)
    {
        $default= [ 'id' => $name, 'type' => 'text', 'value' => ''];
        $options += $default;
        if (array_key_exists('type', $options) && strtolower($options['type']) === 'textarea') {
            $value = $options['value'];
            unset($options['type']);
            unset($options['value']);
            return sprintf('<textarea name="%s"%s>%s</textarea>', $name, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), rtrim($this->_buildAttr($options)));
        }

        if (array_key_exists('type', $options) && strtolower($options['type']) === 'submit') {
            $value = $options['value'];
            unset($options['value']);
            return sprintf('<button name="%s"%s>%s</button>', $name, rtrim($this->_buildAttr($options)), htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }

        if (empty($options['value'])) {
            unset($options['value']);
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
