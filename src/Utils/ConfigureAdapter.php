<?php

declare(strict_types=1);

namespace CAMOO\Utils;

use ConfigInterop\ConfigInterface;

final class ConfigureAdapter implements ConfigInterface
{
    public function __construct(private string $prefix = '')
    {
    }

    public function has(string $key): bool
    {
        $fullKey = $this->resolveKey($key);

        return Configure::check($fullKey) && Configure::read($fullKey) !== null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $fullKey = $this->resolveKey($key);
        if (!Configure::check($fullKey)) {
            return $default;
        }

        return Configure::read($fullKey);
    }

    public function all(): array
    {
        $data = Configure::get();
        if ($this->prefix === '') {
            return $data;
        }

        return $this->scopeArray($data, $this->prefix);
    }

    public function withPrefix(string $prefix): static
    {
        if ($prefix === '') {
            return $this;
        }

        $clone = clone $this;
        $clone->prefix = $this->prefix !== ''
            ? $this->prefix . '.' . $prefix
            : $prefix;

        return $clone;
    }

    private function resolveKey(string $key): string
    {
        return $this->prefix !== '' ? $this->prefix . '.' . $key : $key;
    }

    private function scopeArray(array $data, string $prefix): array
    {
        $keys = explode('.', $prefix);
        foreach ($keys as $key) {
            if (!is_array($data) || !array_key_exists($key, $data)) {
                return [];
            }
            $data = $data[$key];
        }

        return is_array($data) ? $data : [];
    }
}
