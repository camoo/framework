<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Http\Middleware;

use CAMOO\Http\Middleware\AuthenticationMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(AuthenticationMiddleware::class)]
final class AuthenticationMiddlewareTest extends TestCase
{
    public function testUnauthenticatedRequestIsRejected(): void
    {
        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): Response
            {
                return new Response(200);
            }
        };

        $middleware = new AuthenticationMiddleware(static fn (): mixed => null, new Response(401));

        self::assertSame(401, $middleware->process(new ServerRequest('GET', '/'), $handler)->getStatusCode());
    }

    public function testAuthenticatedIdentityIsAttachedToRequest(): void
    {
        $handler = new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): Response
            {
                return new Response($request->getAttribute('identity') === 'user-1' ? 200 : 500);
            }
        };

        $middleware = new AuthenticationMiddleware(static fn (): string => 'user-1', new Response(401));

        self::assertSame(200, $middleware->process(new ServerRequest('GET', '/'), $handler)->getStatusCode());
    }
}
