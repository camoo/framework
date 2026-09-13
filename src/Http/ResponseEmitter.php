<?php

declare(strict_types=1);

namespace CAMOO\Http;

use Psr\Http\Message\ResponseInterface;

/** Emits a PSR-7 response at the server boundary. */
final class ResponseEmitter
{
    public function emit(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        $headers = $response->getHeaders();
        $defaults = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; base-uri 'self'; frame-ancestors 'self'",
        ];
        if (isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            $defaults['Strict-Transport-Security'] = 'max-age=31536000';
        }
        foreach ($defaults as $name => $value) {
            if (!array_key_exists($name, $headers)) {
                $headers[$name] = [$value];
            }
        }

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read(8192);
        }
    }
}
