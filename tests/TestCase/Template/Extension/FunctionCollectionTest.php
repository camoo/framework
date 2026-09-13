<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Template\Extension;

use CAMOO\Interfaces\TemplateFunctionInterface;
use CAMOO\Template\Extension\FunctionCollection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class DummyTemplateFunction implements TemplateFunctionInterface
{
    public function getFunctions(): array
    {
        return [new TwigFunction('dummy_func', fn () => 'ok')];
    }
}

#[CoversClass(FunctionCollection::class)]
class FunctionCollectionTest extends TestCase
{
    public function testCollectionOperations(): void
    {
        $dummy = new DummyTemplateFunction();
        $collection = new FunctionCollection([$dummy]);

        $this->assertCount(1, $collection);
        $this->assertTrue(isset($collection[0]));

        $this->assertInstanceOf(TwigFunction::class, $collection[0]);
        $this->assertSame(0, $collection->key());
        $this->assertInstanceOf(TwigFunction::class, $collection->current());

        $collection->next();
        $this->assertFalse($collection->valid());
        $collection->rewind();
        $this->assertSame(0, $collection->key());

        $collection->add(new DummyTemplateFunction());
        $this->assertCount(2, $collection);

        unset($collection[1]);
        $this->assertCount(1, $collection);
    }

    public function testInvalidTypeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FunctionCollection(['invalid']);
    }
}
