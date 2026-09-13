<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Http;

use CAMOO\Exception\Exception;
use CAMOO\Exception\Http\MethodNotAllowedException;
use CAMOO\Http\Cookie;
use CAMOO\Http\Flash;
use CAMOO\Http\ServerRequest;
use CAMOO\Http\SessionSegment;
use GuzzleHttp\Psr7\ServerRequest as GuzzleRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerRequest::class)]
class ServerRequestTest extends TestCase
{
    private ServerRequest $request;
    private GuzzleRequest $guzzleRequest;

    public function setUp(): void
    {
        $this->guzzleRequest = new GuzzleRequest('POST', '/test', ['Host' => 'example.com'], 'body=1', '1.1', ['REMOTE_ADDR' => '127.0.0.1']);
        $this->guzzleRequest = $this->guzzleRequest->withQueryParams(['page' => '1'])
                                                   ->withParsedBody(['username' => 'camoo', '__csrf_Token' => 'token123']);
        $this->request = new ServerRequest($this->guzzleRequest);
    }

    public function testGetters(): void
    {
        $this->assertSame('POST', $this->request->getMethod());
        $this->assertSame('/test', $this->request->getRequestTarget());
        $this->assertSame('127.0.0.1', $this->request->getRemoteIp());
        $this->assertSame('127.0.0.1', $this->request->getEnv('REMOTE_ADDR'));
        $this->assertInstanceOf(SessionSegment::class, $this->request->getSession());
        $this->assertInstanceOf(Cookie::class, $this->request->cookie);
        $this->assertInstanceOf(Flash::class, $this->request->Flash);
        $this->assertSame([], $this->request->getCookieParams());
        $this->assertNull($this->request->getAttribute('attr'));
        $this->assertNull($this->request->getReferer());
    }

    public function testGetDataAndQuery(): void
    {
        $this->assertSame('camoo', $this->request->getData('username'));
        $this->assertSame('token123', $this->request->getRawData('__csrf_Token'));
        $this->assertSame('1', $this->request->getQuery('page'));
        $this->assertSame('camoo', $this->request->data('username'));
        $this->assertSame('1', $this->request->query('page'));
        $this->assertIsArray($this->request->getData());
        $this->assertIsArray($this->request->getQuery());
        $this->assertSame(['username' => 'camoo'], $this->request->getdata());
        $this->assertSame(['page' => '1'], $this->request->getquery());
    }

    public function testIsMethod(): void
    {
        $this->assertTrue($this->request->is('post'));
        $this->assertFalse($this->request->is('get'));

        $this->expectException(Exception::class);
        $this->request->is('invalid_method');
    }

    public function testAjaxMethodValid(): void
    {
        $guzzle = new GuzzleRequest('GET', '/test', [
            'Host' => 'example.com',
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => 'http://example.com/page',
        ], null, '1.1', [
            'HTTP_HOST' => 'example.com',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => 'http://example.com/page',
        ]);
        $req = new ServerRequest($guzzle);
        $this->assertTrue($req->is('ajax'));
    }

    public function testAjaxMethodDoesNotAuthorizeByReferrer(): void
    {
        $guzzle = new GuzzleRequest('GET', '/test', [], null, '1.1', [
            'HTTP_HOST' => 'example.com',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_REFERER' => 'http://malicious-domain.com/page',
        ]);
        $req = new ServerRequest($guzzle);

        $this->assertTrue($req->is('ajax'));
    }

    public function testAllowMethod(): void
    {
        $this->request->allowMethod(['post', 'put']);

        $this->expectException(MethodNotAllowedException::class);
        $this->request->allowMethod(['get']);
    }

    public function testAllowMethodEmptyThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->request->allowMethod([]);
    }

    public function testProxyIpResolution(): void
    {
        $guzzle = new GuzzleRequest('GET', '/', [], null, '1.1', [
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1, 10.0.0.2',
        ]);
        $req = new ServerRequest($guzzle);
        $req->isProxy = true;
        $this->assertSame('10.0.0.2', $req->getRemoteIp());

        $guzzleClient = new GuzzleRequest('GET', '/', [], null, '1.1', [
            'HTTP_CLIENT_IP' => '10.0.0.5',
        ]);
        $reqClient = new ServerRequest($guzzleClient);
        $reqClient->isProxy = true;
        $this->assertSame('10.0.0.5', $reqClient->getRemoteIp());
    }

    public function testNullServerRequest(): void
    {
        $req = new ServerRequest(null);
        $this->assertSame('GET', $req->getMethod());
        $this->assertSame('/', $req->getRequestTarget());
        $this->assertNull($req->getEnv('NON_EXISTENT'));
    }

    public function testMagicCallThrowsForInvalidMethod(): void
    {
        $this->expectException(Exception::class);
        $this->request->invalidMagicMethod();
    }
}
