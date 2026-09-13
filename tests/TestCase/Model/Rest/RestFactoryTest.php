<?php

namespace CAMOO\Test\TestCase\Model\Rest;

use CAMOO\Exception\Exception;
use CAMOO\Model\Rest\RestFactory;
use CAMOO\Model\Rest\RestLocatorTrait;
use CAMOO\Utils\Configure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class DummyClassUsingRestLocator
{
    use RestLocatorTrait;
}

#[CoversClass(RestFactory::class)]
#[CoversClass(RestLocatorTrait::class)]
class RestFactoryTest extends TestCase
{
    public function testCreateSingleton(): void
    {
        $factory1 = RestFactory::create();
        $factory2 = RestFactory::create();
        $this->assertInstanceOf(RestFactory::class, $factory1);
        $this->assertInstanceOf(RestFactory::class, $factory2);
    }

    public function testGetThrowsExceptionForUnknownClass(): void
    {
        Configure::write('App.namespace', 'CAMOO\\Test');
        $factory = RestFactory::create();
        $this->expectException(Exception::class);
        $factory->get('UnknownRestModel');
    }

    public function testRestLocatorTrait(): void
    {
        $dummy = new DummyClassUsingRestLocator();
        $this->assertInstanceOf(RestFactory::class, $dummy->getRestLocator());
    }
}
