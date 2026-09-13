<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Testing;

use CAMOO\Controller\AppController;
use CAMOO\Event\EventInterface;
use CAMOO\TestCase\ControllerTestCase;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;

class TestableController extends AppController
{
    public function index(): Response
    {
        return new Response(201, ['X-Test' => 'value'], 'created');
    }
}

class EarlyResponseTestableController extends AppController
{
    public function beforeAction(EventInterface $event): void
    {
        $event->setResult(new Response(202, [], 'accepted'));
    }
}

#[CoversClass(ControllerTestCase::class)]
final class ControllerTestCaseTest extends ControllerTestCase
{
    public function testCreatesAndDispatchesControllerAction(): void
    {
        $controller = $this->createController(TestableController::class, 'index', 'POST', '/items');
        $response = $this->dispatchAction($controller);

        self::assertSame('POST', $controller->request->getMethod());
        self::assertSame(201, $response->getStatusCode());
        self::assertSame('created', (string)$response->getBody());
    }

    public function testDispatchesLifecycleEarlyResponse(): void
    {
        $controller = $this->createController(EarlyResponseTestableController::class);
        $response = $this->dispatchAction($controller);

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('accepted', (string)$response->getBody());
    }
}
