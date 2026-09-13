<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Event;

use CAMOO\Event\Event;
use CAMOO\Event\EventManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Event::class)]
#[CoversClass(EventManager::class)]
class EventTest extends TestCase
{
    public function testEventAndEventManager(): void
    {
        $eventManager = new EventManager();
        $this->assertInstanceOf(EventManager::class, $eventManager);

        $event = new Event('Custom.event', $this, ['data' => 123]);
        $this->assertSame('Custom.event', $event->getName());
        $this->assertSame($this, $event->getSubject());
        $this->assertSame(['data' => 123], $event->getData());

        $eventManager->dispatch($event);
        $this->assertTrue(true);
    }
}
