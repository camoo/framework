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

        foreach ($response->getHeaders() as $name => $values) {
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
