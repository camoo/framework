<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension\Functions;

use CAMOO\Http\ServerRequest;
use CAMOO\Http\SessionSegment;
use CAMOO\Interfaces\TemplateFunctionInterface;
use CAMOO\Utils\Security;
use Twig\TwigFunction;

/**
 * Class Form
 *
 * @author CamooSarl
 */
final class Form implements TemplateFunctionInterface
{
    private array $hiddenValue = [];

    public function __construct(
        private ServerRequest $request,
        private ?SessionSegment $csrfSessionSegment,
        private ?string $token = null
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('form_start', [$this, 'formStart'], ['is_safe' => ['html']]),
            new TwigFunction('form_end', [$this, 'formEnd'], ['is_safe' => ['html']]),
            new TwigFunction('form_input', [$this, 'input'], ['is_safe' => ['html']]),
        ];
    }

    public function formStart(?string $name = null, array $options = []): string
    {
        $token = $this->token;
        $name = $name ?? uniqid('form');
        $default = ['id' => $name, 'method' => 'POST', 'action' => $this->request->getRequestTarget()];
        if (array_key_exists('url', $options)) {
            $options['action'] = $options['url'];
            unset($options['url']);
        }
        $options += $default;
        $inputToken = $token !== null ? ' <input type="hidden" name="__csrf_Token" value="' . $token . '" />' : '';

        return sprintf('<form name="%s"%s>' . "\n" . '%s', $name, $this->buildAttribute($options), $inputToken);
    }

    public function formEnd(): string
    {
        return '</form>';
    }

    public function input(string $name, array $options = []): string
    {
        $default = ['id' => $name, 'type' => 'text', 'value' => ''];
        $options += $default;
        if (array_key_exists('type', $options) && strtolower($options['type']) === 'textarea') {
            $value = $options['value'];
            unset($options['type']);
            unset($options['value']);

            return sprintf(
                '<textarea name="%s"%s>%s</textarea>',
                $name,
                rtrim($this->buildAttribute($options)),
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }

        if (array_key_exists('type', $options) && strtolower($options['type']) === 'submit') {
            $value = $options['value'];
            unset($options['value']);

            return sprintf(
                '<button %s>%s</button>',
                rtrim($this->buildAttribute($options)),
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
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
            $this->csrfSessionSegment?->write('__csrf_checksum', $this->hiddenValue);
        }

        return sprintf('<input name="%s"%s />', $name, rtrim($this->buildAttribute($options)));
    }

    private function buildAttribute(array $options): string
    {
        $attributes = ' ';
        foreach ($options as $attr => $option) {
            if (strtolower($attr) === 'value' && !empty($option)) {
                $option = htmlspecialchars($option, ENT_QUOTES, 'UTF-8');
            }
            $attributes .= $attr . '="' . $option . '" ';
        }

        return $attributes;
    }
}
