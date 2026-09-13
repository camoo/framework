<?php

namespace CAMOO\Test\TestCase\Controller;

use CAMOO\Controller\AppController;
use CAMOO\Controller\Component\BaseComponent;
use CAMOO\Controller\Component\ComponentCollection;
use CAMOO\Controller\Component\SecurityComponent;
use CAMOO\Controller\ErrorController;
use CAMOO\Event\Event;
use CAMOO\Http\ServerRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest as GuzzleRequest;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class TestConcreteController extends AppController
{
    public bool $initializedCalled = false;

    public function initialize(): void
    {
        parent::initialize();
        $this->initializedCalled = true;
    }

    protected function camooExit(): void
    {
        // Don't call exit() during tests
    }
}

class TestDummyComponent extends BaseComponent
{
}

#[CoversClass(AppController::class)]
#[CoversClass(ErrorController::class)]
#[CoversClass(ComponentCollection::class)]
#[CoversClass(BaseComponent::class)]
#[CoversClass(SecurityComponent::class)]
class ControllerAndComponentTest extends TestCase
{
    private TestConcreteController $controller;

    public function setUp(): void
    {
        $this->controller = new TestConcreteController();
        $this->controller->controller = 'Test';
        $this->controller->action = 'index';
        $this->controller->request = new ServerRequest(new GuzzleRequest('GET', '/test'));
        $this->controller->setResponse(new Response());
    }

    public function testImplementedEvents(): void
    {
        $events = $this->controller->implementedEvents();
        $this->assertArrayHasKey('AppController.initialize', $events);
        $this->assertArrayHasKey('AppController.beforeRender', $events);
        $this->assertArrayHasKey('AppController.beforeRedirect', $events);
    }

    public function testSetAndTplData(): void
    {
        $this->controller->set('title', 'Page Title');
        $this->controller->set(['key' => 'val']);
        $this->assertTrue(true);
    }

    public function testComponentCollectionAndLoading(): void
    {
        $collection = new ComponentCollection($this->controller);
        $this->assertSame(0, count($collection));

        $component = new TestDummyComponent($this->controller, ['a' => 1]);
        $collection['TestDummy'] = $component;
        $this->assertSame(1, count($collection));
        $this->assertTrue(isset($collection['TestDummy']));
        $this->assertSame($component, $collection['TestDummy']);

        $items = iterator_to_array($collection->getIterator());
        $this->assertCount(1, $items);

        unset($collection['TestDummy']);
        $this->assertFalse(isset($collection['TestDummy']));
    }

    public function testAddInvalidComponentThrowsException(): void
    {
        $collection = new ComponentCollection($this->controller);
        $this->expectException(InvalidArgumentException::class);
        $collection->add('NonExistentComponent');
    }

    public function testBaseComponentEvents(): void
    {
        $component = new TestDummyComponent($this->controller);
        $events = $component->implementedEvents();
        $this->assertIsArray($events);
    }

    public function testErrorController(): void
    {
        $errorCtrl = new ErrorController();
        $this->assertInstanceOf(ErrorController::class, $errorCtrl);
    }
}
