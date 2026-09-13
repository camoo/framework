<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Utils;

use CAMOO\Utils\Configure;
use CAMOO\Utils\ConfigureAdapter;
use ConfigInterop\ConfigInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigureAdapter::class)]
#[CoversClass(Configure::class)]
class ConfigureAdapterTest extends TestCase
{
    public function setUp(): void
    {
        Configure::write('App.name', 'CamooApp');
        Configure::write('App.debug', true);
        Configure::write('Database.host', 'localhost');
        Configure::write('Database.password', null);
    }

    public function testToInteropReturnsConfigInterface(): void
    {
        $interop = Configure::toInterop();
        $this->assertInstanceOf(ConfigInterface::class, $interop);
        $this->assertTrue($interop->has('App.name'));
        $this->assertSame('CamooApp', $interop->get('App.name'));
        $this->assertFalse($interop->has('Database.password'));
        $this->assertNull($interop->get('Database.password', 'default'));
        $this->assertSame('default', $interop->get('NonExistent', 'default'));
    }

    public function testWithPrefix(): void
    {
        $scoped = Configure::toInterop('Database');
        $this->assertTrue($scoped->has('host'));
        $this->assertSame('localhost', $scoped->get('host'));

        $all = $scoped->all();
        $this->assertArrayHasKey('host', $all);
        $this->assertSame('localhost', $all['host']);
    }
}
