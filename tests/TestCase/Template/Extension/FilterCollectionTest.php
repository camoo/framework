<?php

namespace CAMOO\Test\TestCase\Template\Extension;

use CAMOO\Interfaces\TemplateFilterInterface;
use CAMOO\Template\Extension\FilterCollection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class DummyTemplateFilter implements TemplateFilterInterface
{
    public function getFilters(): array
    {
        return [new TwigFilter('dummy_filter', fn($v) => $v)];
    }
}

#[CoversClass(FilterCollection::class)]
class FilterCollectionTest extends TestCase
{
    public function testCollectionOperations(): void
    {
        $dummy = new DummyTemplateFilter();
        $collection = new FilterCollection([$dummy]);

        $this->assertCount(1, $collection);
        $this->assertTrue(isset($collection[0]));

        $this->assertInstanceOf(TwigFilter::class, $collection[0]);
        $this->assertSame(0, $collection->key());
        $this->assertInstanceOf(TwigFilter::class, $collection->current());

        $collection->next();
        $this->assertFalse($collection->valid());
        $collection->rewind();
        $this->assertSame(0, $collection->key());

        $collection->add(new DummyTemplateFilter());
        $this->assertCount(2, $collection);

        unset($collection[1]);
        $this->assertCount(1, $collection);
    }

    public function testInvalidTypeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FilterCollection(['not_a_filter']);
    }
}
