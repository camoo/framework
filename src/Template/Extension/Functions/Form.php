<?php

declare(strict_types=1);

namespace CAMOO\Template\Extension\Functions;

use CAMOO\Http\ServerRequest;
use CAMOO\Http\Session;
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

    private readonly SessionSegment $csrfSessionSegment;

    private readonly string $token;

    public function __construct(
        private readonly ServerRequest $request,
        ?SessionSegment $csrfSessionSegment = null,
        ?string $token = null,
    ) {
        $session = Session::create();
        $this->csrfSessionSegment = $csrfSessionSegment ?? new SessionSegment(
            $session->segment(\Aura\Session\CsrfToken::class),
        );
        $this->token = $token ?? $session->getCsrfToken()->getValue();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('form_start', $this->formStart(...), ['is_safe' => ['html']]),
            new TwigFunction('form_end', $this->formEnd(...), ['is_safe' => ['html']]),
            new TwigFunction('form_input', $this->input(...), ['is_safe' => ['html']]),
        ];
    }

    public function formStart(?string $name = null, array $options = []): string
    {
        $name ??= uniqid('form');
        $default = ['id' => $name, 'method' => 'POST', 'action' => $this->request->getRequestTarget()];
        if (array_key_exists('url', $options)) {
            $options['action'] = $options['url'];
            unset($options['url']);
        }
        $options += $default;
        $inputToken = ' <input type="hidden" name="__csrf_Token" value="' .
            $this->escape($this->token) . '" />';

        return sprintf(
            '<form name="%s"%s>' . "\n" . '%s',
            $this->escape($name),
            $this->buildAttribute($options),
            $inputToken,
        );
    }

    public function formEnd(): string
    {
        return '</form>';
    }

    public function input(string $name, array $options = []): string
    {
        $default = ['id' => $name, 'type' => 'text', 'value' => ''];
        $options += $default;
        $type = strtolower((string)$options['type']);
        if ($type === 'textarea') {
            $value = (string)$options['value'];
            unset($options['type']);
            unset($options['value']);

            return sprintf(
                '<textarea name="%s"%s>%s</textarea>',
                $this->escape($name),
                rtrim($this->buildAttribute($options)),
                $this->escape($value),
            );
        }

        if ($type === 'submit') {
            $value = (string)$options['value'];
            unset($options['value']);

            return sprintf(
                '<button %s>%s</button>',
                rtrim($this->buildAttribute($options)),
                $this->escape($value),
            );
        }

        if (array_key_exists('value', $options) && $options['value'] === '') {
            unset($options['value']);
        }

        if ($type !== 'email' && $name === 'email') {
            $options['type'] = 'email';
        }

        if ($type !== 'password' && $name === 'password') {
            $options['type'] = 'password';
        }

        if ($type === 'hidden') {
            $this->hiddenValue[$name] = hash('sha256', Security::satanizer((string)($options['value'] ?? '')));
            $this->csrfSessionSegment->write('__csrf_checksum', $this->hiddenValue);
        }

        if (array_key_exists('value', $options) && $options['value'] === '') {
            unset($options['value']);
        }

        return sprintf('<input name="%s"%s />', $this->escape($name), rtrim($this->buildAttribute($options)));
    }

    private function buildAttribute(array $options): string
    {
        $attributes = ' ';
        foreach ($options as $attr => $option) {
            $attributes .= $attr . '="' . $this->escape((string)$option) . '" ';
        }

        return $attributes;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
