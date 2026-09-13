<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Controller;

use CAMOO\Controller\AppController;
use CAMOO\Controller\Component\BaseComponent;
use CAMOO\Controller\Component\ComponentCollection;
use CAMOO\Controller\Component\SecurityComponent;
use CAMOO\Event\Event;
use CAMOO\Event\EventInterface;
use CAMOO\Exception\Exception;
use CAMOO\Exception\Http\BadRequestException;
use CAMOO\Http\ServerRequest;
use CAMOO\Utils\Configure;
use CAMOO\Validation\Adapters\Cake\Validator;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest as GuzzleRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class FullTestController extends AppController
{
    public bool $customBeforeActionCalled = false;

    public function initialize(): void
    {
        parent::initialize();
    }

    public function beforeAction($event): void
    {
        $this->customBeforeActionCalled = true;
    }

    protected function camooExit(): void
    {
    }
}

class TestSecurityController extends AppController
{
    public ?SecurityComponent $Security = null;

    protected function camooExit(): void
    {
    }
}

class DummyComponent extends BaseComponent
{
}

class EarlyResponseController extends AppController
{
    public function beforeAction(EventInterface $event): void
    {
        $event->setResult(new Response(204));
    }
}

#[CoversClass(AppController::class)]
#[CoversClass(SecurityComponent::class)]
#[CoversClass(ComponentCollection::class)]
#[CoversClass(BaseComponent::class)]
class AppControllerFullTest extends TestCase
{
    private FullTestController $controller;

    public function setUp(): void
    {
        Configure::write('Security.csrf_lifetime', 1800);
        Configure::write('Security.csrf_single_once', false);

        $this->controller = new FullTestController();
        $this->controller->controller = 'Pages';
        $this->controller->action = 'index';
        $this->controller->request = new ServerRequest(new GuzzleRequest('GET', '/pages/index'));
        $this->controller->setResponse(new Response());
    }

    public function testWakeUpControllerAndRender(): void
    {
        $this->controller->wakeUpController();
        $this->assertInstanceOf(ComponentCollection::class, $this->controller->getComponentCollection());
        $this->assertFalse($this->controller->hasComponent('Security'));
    }

    public function testWakeUpControllerReturnsEventResponse(): void
    {
        $controller = new EarlyResponseController();
        $controller->controller = 'Pages';
        $controller->action = 'index';
        $controller->request = new ServerRequest(new GuzzleRequest('GET', '/pages/index'));
        $controller->setResponse(new Response());

        $response = $controller->wakeUpController();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testLoadComponent(): void
    {
        $this->controller->wakeUpController();
        $this->controller->loadComponent('Security');
        $this->assertTrue($this->controller->hasComponent('Security'));
    }

    public function testSecurityComponentWakeUpGet(): void
    {
        $secCtrl = new TestSecurityController();
        $secCtrl->controller = 'Pages';
        $secCtrl->action = 'index';
        $secCtrl->request = new ServerRequest(new GuzzleRequest('GET', 'http://localhost/test', ['HTTP_HOST' => 'localhost']));
        $secCtrl->loadComponent('Security');

        $event = new Event('AppController.wakeUp', $secCtrl);
        $secCtrl->Security->wakeUp($event);
        $this->assertNotEmpty($secCtrl->Security->csrf_Token);
    }

    public function testSecurityComponentPostMissingTokenThrowsException(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_REFERER'] = 'http://localhost/test';
        $_POST = [];

        $secCtrl = new TestSecurityController();
        $secCtrl->controller = 'Pages';
        $secCtrl->action = 'index';
        $guzzle = new GuzzleRequest('POST', 'http://localhost/test', ['Host' => 'localhost'], '', '1.1', [
            'HTTP_HOST' => 'localhost',
            'HTTP_REFERER' => 'http://localhost/test',
        ]);
        $secCtrl->request = new ServerRequest($guzzle);
        $secCtrl->loadComponent('Security');

        $event = new Event('AppController.wakeUp', $secCtrl);
        $this->expectException(BadRequestException::class);
        $secCtrl->Security->wakeUp($event);
    }

    public function testShowValidateErrors(): void
    {
        $validator = new Validator();
        $validator->requirePresence('email')->email('email');
        $validator->isValid([]);

        $this->controller->wakeUpController();
        $refMethod = new \ReflectionMethod($this->controller, 'showValidateErrors');
        $refMethod->setAccessible(true);
        $refMethod->invoke($this->controller, $validator);

        $this->assertNotNull($this->controller->request->Flash);
    }

    public function testSetVariables(): void
    {
        $this->controller->set('title', 'My Page Title');
        $this->controller->set(['key1' => 'val1', 'key2' => 'val2']);

        $refProp = new \ReflectionProperty($this->controller, 'tplData');
        $refProp->setAccessible(true);
        $data = $refProp->getValue($this->controller);

        $this->assertSame('My Page Title', $data['title']);
        $this->assertSame('val1', $data['key1']);
        $this->assertSame('val2', $data['key2']);
    }

    public function testSetEmptyVarNameThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->controller->set('', 'val');
    }

    public function testSetSerialize(): void
    {
        $this->controller->set('_serialize', ['status' => 'ok']);

        $response = $this->getResponse();
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('"status":"ok"', (string)$response->getBody());
    }

    public function testJsonResponse(): void
    {
        $refMethod = new \ReflectionMethod($this->controller, 'jsonResponse');
        $refMethod->setAccessible(true);

        $refMethod->invoke($this->controller, ['result' => 'success']);

        $response = $this->getResponse();
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('"result":"success"', (string)$response->getBody());
    }

    public function testRedirect(): void
    {
        putenv('HTTP_HOST=localhost');
        putenv('SERVER_PROTOCOL=1.1');

        @$this->controller->redirect('/new-page');
        @$this->controller->redirect('http://example.com/external', true);
        $this->assertTrue(true);
    }

    public function testRedirectEmptyDestinationThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->controller->redirect('');
    }

    public function testGetReferer(): void
    {
        $refMethod = new \ReflectionMethod($this->controller, 'getReferer');
        $refMethod->setAccessible(true);
        $this->assertNull($refMethod->invoke($this->controller));
    }

    public function testImplementedEventsAndEmptyHooks(): void
    {
        $events = $this->controller->implementedEvents();
        $this->assertArrayHasKey('AppController.initialize', $events);

        $event = new Event('AppController.initialize', $this->controller);
        $this->controller->beforeRender($event);
        $this->controller->beforeAction($event);
        $this->controller->beforeRedirect($event);
        $this->assertTrue(true);
    }

    public function testComponentCollectionOperations(): void
    {
        $this->controller->wakeUpController();
        $collection = $this->controller->getComponentCollection();

        $security = $collection->load('Security');
        $this->assertInstanceOf(SecurityComponent::class, $security);
        $this->assertTrue($collection->has('Security'));
        $this->assertSame($security, $collection->get('Security'));
        $this->assertSame($security, $collection->Security);
        $this->assertContains('Security', $collection->loaded());

        $collection->unload('Security');
        $this->assertFalse($collection->has('Security'));
    }

    public function testComponentCollectionGetNonExistent(): void
    {
        $this->controller->wakeUpController();
        $collection = $this->controller->getComponentCollection();
        $this->assertNull($collection->get('NonExistentComponent'));
    }

    public function testBaseComponentMethods(): void
    {
        $this->controller->wakeUpController();
        $component = new DummyComponent($this->controller->getComponentCollection());
        $this->assertSame(['BaseComponent.initialize' => 'beforeAction'], $component->implementedEvents());
        $component->beforeAction(new Event('test'));
        $component->shutdown(new Event('test'));
        $component->beforeRedirect(new Event('test'));
        $this->assertTrue(true);
    }

    private function getResponse(): \Psr\Http\Message\ResponseInterface
    {
        $property = new \ReflectionProperty($this->controller, 'response');

        return $property->getValue($this->controller);
    }
}
