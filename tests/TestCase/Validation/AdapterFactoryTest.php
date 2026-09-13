<?php

namespace CAMOO\Test\TestCase\Validation;

use CAMOO\Exception\Exception;
use CAMOO\Interfaces\ValidationInterface;
use CAMOO\Validation\AdapterFactory;
use CAMOO\Validation\Adapters\Cake\Validator;
use CAMOO\Validation\ValidatorLocatorTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class DummyClassUsingValidatorLocator
{
    use ValidatorLocatorTrait;
}

#[CoversClass(AdapterFactory::class)]
#[CoversClass(Validator::class)]
#[CoversClass(ValidatorLocatorTrait::class)]
class AdapterFactoryTest extends TestCase
{
    public function testCreateAndGet(): void
    {
        $factory = AdapterFactory::create();
        $this->assertInstanceOf(AdapterFactory::class, $factory);

        $validator = $factory->get();
        $this->assertInstanceOf(ValidationInterface::class, $validator);
        $this->assertInstanceOf(Validator::class, $validator);
    }

    public function testGetInvalidAdapterThrowsException(): void
    {
        $factory = AdapterFactory::create();
        $this->expectException(Exception::class);
        $factory->get('Validator', 'InvalidAdapter');
    }

    public function testValidatorValidation(): void
    {
        $validator = new Validator();
        $validator->requirePresence('title');

        $this->assertFalse($validator->isValid([]));
        $this->assertNotEmpty($validator->getErrors());

        $this->assertTrue($validator->isValid(['title' => 'Sample']));
        $this->assertEmpty($validator->getErrors());
    }

    public function testValidatorLocatorTrait(): void
    {
        $dummy = new DummyClassUsingValidatorLocator();
        $this->assertInstanceOf(AdapterFactory::class, $dummy->getValidatorLocator());
    }
}
