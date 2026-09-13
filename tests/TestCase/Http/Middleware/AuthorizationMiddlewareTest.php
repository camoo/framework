<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Http\Middleware;

use CAMOO\Http\Middleware\AuthorizationMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(AuthorizationMiddleware::class)]
final class AuthorizationMiddlewareTest extends TestCase
{
    public function testDeniedRequestDoesNotReachHandler(): void
    {
        $handler = new class () implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): Response
            {
                return new Response(200);
            }
        };

        $middleware = new AuthorizationMiddleware(
            static fn (): bool => false,
            new Response(403),
        );

        self::assertSame(403, $middleware->process(new ServerRequest('GET', '/admin'), $handler)->getStatusCode());
    }

    public function testAllowedRequestReachesHandler(): void
    {
        $middleware = new AuthorizationMiddleware(
            static fn (): bool => true,
            new Response(403),
        );
        $handler = new class () implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): Response
            {
                return new Response(200);
            }
        };

        self::assertSame(200, $middleware->process(new ServerRequest('GET', '/admin'), $handler)->getStatusCode());
    }
}
