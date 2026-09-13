<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Utils;

use CAMOO\Utils\ConfigTrait;
use CAMOO\Utils\Utility;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class DummyClassUsingConfigTrait
{
    use ConfigTrait;

    protected array $_defaultConfig = ['theme' => 'default'];
}

#[CoversClass(Utility::class)]
#[CoversClass(ConfigTrait::class)]
class UtilityTest extends TestCase
{
    public function testIsGsm0338(): void
    {
        $this->assertTrue(Utility::isGsm0338('Hello World! 123 @'));
        $this->assertFalse(Utility::isGsm0338('Bonjour 🚀'));
    }

    public function testIsCli(): void
    {
        $this->assertIsBool(Utility::isCli());
    }

    public function testConfigTrait(): void
    {
        $dummy = new DummyClassUsingConfigTrait();
        $this->assertSame('default', $dummy->getConfig('theme'));
        $dummy->setConfig('theme', 'dark');
        $this->assertSame('dark', $dummy->getConfig('theme'));
    }
}
