<?php

declare(strict_types=1);

namespace CAMOO\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Resolves the current identity before authorization and controller dispatch. */
final readonly class AuthenticationMiddleware implements MiddlewareInterface
{
    /** @param callable(ServerRequestInterface): mixed $authenticate */
    public function __construct(
        private mixed $authenticate,
        private ResponseInterface $unauthorizedResponse,
        private string $identityAttribute = 'identity',
        private bool $required = true,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = ($this->authenticate)($request);
        if ($identity === null && $this->required) {
            return $this->unauthorizedResponse;
        }

        if ($identity !== null) {
            $request = $request->withAttribute($this->identityAttribute, $identity);
        }

        return $handler->handle($request);
    }
}
