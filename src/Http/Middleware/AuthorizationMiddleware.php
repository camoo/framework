<?php

declare(strict_types=1);

namespace CAMOO\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Enforces application-provided authorization before dispatching a request. */
final readonly class AuthorizationMiddleware implements MiddlewareInterface
{
    /** @param callable(ServerRequestInterface): bool $authorize */
    public function __construct(
        private mixed $authorize,
        private ResponseInterface $forbiddenResponse,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!(bool)($this->authorize)($request)) {
            return $this->forbiddenResponse;
        }

        return $handler->handle($request);
    }
}
