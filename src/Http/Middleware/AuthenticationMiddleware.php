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
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = ($this->authenticate)($request);
        if ($identity === null) {
            return $this->unauthorizedResponse;
        }

        return $handler->handle($request->withAttribute($this->identityAttribute, $identity));
    }
}
