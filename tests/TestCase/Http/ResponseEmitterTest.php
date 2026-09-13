<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Http;

use CAMOO\Http\ResponseEmitter;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResponseEmitter::class)]
final class ResponseEmitterTest extends TestCase
{
    public function testEmitsResponseBody(): void
    {
        $response = new Response(201, ['X-Test' => 'value'], 'response body');

        ob_start();
        (new ResponseEmitter())->emit($response);
        $body = ob_get_clean();

        $this->assertSame('response body', $body);
        $this->assertSame(201, http_response_code());
    }
}
