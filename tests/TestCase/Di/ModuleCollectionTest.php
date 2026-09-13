<?php

namespace CAMOO\Test\TestCase\Di;

use CAMOO\Di\Module\ModuleCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ModuleCollection::class)]
class ModuleCollectionTest extends TestCase
{
    public function testCollectionAddAndIterator(): void
    {
        $collection = new ModuleCollection();
        $collection->add('SomeModule');
        $collection->add(fn() => null);

        $items = iterator_to_array($collection->getIterator());
        $this->assertCount(2, $items);
        $this->assertSame('SomeModule', $items[0]);
    }
}
