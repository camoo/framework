<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Model\Rest;

use ArrayObject;
use CAMOO\Event\Event;
use CAMOO\Exception\Exception;
use CAMOO\Interfaces\ValidationInterface;
use CAMOO\Model\Rest\AppRest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class TestRemoteObject
{
    public function executeAction($data)
    {
        return 'remote_executed_' . ($data['key'] ?? '');
    }
}

class TestRestConcrete extends AppRest
{
    public function validationDefault(ValidationInterface $validator): ValidationInterface
    {
        return $validator;
    }

    public function validationCustom(ValidationInterface $validator): ValidationInterface
    {
        return $validator;
    }

    public function validationFailing(ValidationInterface $validator): ValidationInterface
    {
        $validator->requirePresence('required_field');

        return $validator;
    }

    public function myRemoteMethod($data)
    {
        return 'remote_' . ($data['test'] ?? 'none');
    }

    public function attachRemote(string $name, object $object): void
    {
        $this->loadRemoteObject($name, $object);
    }
}

#[CoversClass(AppRest::class)]
class AppRestTest extends TestCase
{
    private TestRestConcrete $rest;

    public function setUp(): void
    {
        $this->rest = new TestRestConcrete();
    }

    public function testNewRequestWithoutValidation(): void
    {
        $this->rest->newRequest(['name' => 'Camoo'], false);
        $this->assertTrue($this->rest->has('name'));
        $this->assertSame('Camoo', $this->rest->get('name'));
        $this->assertSame('Camoo', $this->rest->name);
        $this->assertSame('Camoo', $this->rest['name']);
        $this->assertEmpty($this->rest->getErrors());
    }

    public function testNewRequestValidationFails(): void
    {
        $this->rest->newRequest([], true, ['validation' => 'failing']);
        $this->assertNotEmpty($this->rest->getErrors());

        $this->expectException(Exception::class);
        $this->rest->send([$this->rest, 'myRemoteMethod']);
    }

    public function testFailedRequestDoesNotReusePreviousValidPayload(): void
    {
        $this->rest->newRequest(['required_field' => 'first'], true, ['validation' => 'failing']);
        $this->assertSame('first', $this->rest->get('required_field'));

        $this->rest->newRequest([], true, ['validation' => 'failing']);
        $this->assertNotEmpty($this->rest->getErrors());
        $this->assertNull($this->rest->get('required_field'));

        $this->expectException(Exception::class);
        $this->rest->send([$this->rest, 'myRemoteMethod']);
    }

    public function testSetAndUnsetAndIterator(): void
    {
        $this->rest->set('age', 10);
        $this->assertSame(10, $this->rest->get('age'));
        $this->assertTrue(isset($this->rest['age']));

        $this->rest['city'] = 'Yaounde';
        $this->assertSame('Yaounde', $this->rest['city']);

        $this->rest[] = 'array_append';

        $items = iterator_to_array($this->rest->getIterator());
        $this->assertArrayHasKey('age', $items);

        unset($this->rest['age']);
        $this->assertFalse(isset($this->rest['age']));
        $this->assertNull($this->rest->get('non_existent'));
    }

    public function testSendInvalidThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Data Provided !');
        $this->rest->send([$this->rest, 'myRemoteMethod']);
    }

    public function testSendSuccess(): void
    {
        $this->rest->newRequest(['test' => 'hello'], false);
        $res = $this->rest->send([$this->rest, 'myRemoteMethod']);
        $this->assertSame('remote_hello', $res);
    }

    public function testSendWithRemoteObjectStringCallable(): void
    {
        $remoteObj = new TestRemoteObject();
        $this->rest->attachRemote('remoteService', $remoteObj);
        $this->assertFalse(property_exists($this->rest, 'remoteService'));
        $this->rest->newRequest(['key' => 'val1'], false);

        $res = $this->rest->send(['::remoteService', 'executeAction']);
        $this->assertSame('remote_executed_val1', $res);
    }

    public function testImplementedEvents(): void
    {
        $events = $this->rest->implementedEvents();
        $this->assertArrayHasKey('Rest.beforeSend', $events);
        $this->assertArrayHasKey('Rest.afterSend', $events);

        $event = new Event('Rest.beforeSend');
        $this->rest->beforeSend($event, new ArrayObject());
        $this->rest->afterSend($event, null);
        $this->assertTrue(true);
    }

    public function testNewRequestValidationMethodNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->rest->newRequest(['test' => 'val'], true, ['validation' => 'invalid_method_name']);
    }
}
