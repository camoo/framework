<?php
declare(strict_types=1);

namespace CAMOO\Template\Extension\Functions;

use CAMOO\Template\Extension\FunctionHelper as BaseFunction;

/**
 * Class Form
 * @author CamooSarl
 */
class Form extends BaseFunction
{
    public function getFunctions() : array
    {
        return [
            $this->add('form_start', [$this, 'formStart']),
            $this->add('form_end', [$this, 'formEnd']),
        ];
    }

    public function formStart(?string $name=null, ?string $method='POST', string $action='', $options=[])
    {
        $token = $this->request->getCsrfToken();
        $name = $name ?? uniqid('form', false);
        $default= [ 'id' => $name];
        $options += $default;
        $attributes = ' ';
        foreach ($options as $attr => $option) {
            $attributes .= $attr. '="' . $option. '"';
        }

        return sprintf('<form name="%s" method="%s" action="%s"%s>' ."\n".' <input type="hidden" name="__csrf_Token" value="'.$token.'" />', $name, $method, $action, $attributes);
    }

    public function formEnd()
    {
        return '</form>';
    }
}
