<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Utils;

use CAMOO\Utils\Configure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversClass(Configure::class)]
class ConfigureTest extends TestCase
{
    public function setUp(): void
    {
        file_put_contents('/tmp/test_configure1.php', "<?php\nreturn['Config' => ['test' => true]];\n");
        file_put_contents('/tmp/test_configure2.php', "<?php\nreturn['Plugin' => ['version' => '1.0.0']];\n");
    }

    public function tearDown(): void
    {
        unlink('/tmp/test_configure1.php');
        unlink('/tmp/test_configure2.php');
    }

    #[TestWith(['/tmp/test_configure1.php'])]
    public function testInstance($path)
    {
        $this->assertNull(Configure::load($path));
    }

    #[Depends('testInstance')]
    public function testRead()
    {
        $this->assertNull(Configure::read('test.test'));
        $this->assertIsArray(Configure::read('Config'));
    }

    #[Depends('testInstance')]
    public function testCheck()
    {
        $this->assertTrue(Configure::check('Config'));
        $this->assertFalse(Configure::check('epepep'));
    }

    #[Depends('testInstance')]
    public function testGet()
    {
        $this->assertIsArray(Configure::get());
    }

    #[TestWith(['/tmp/test_configure2.php'])]
    public function testMerge($path2)
    {
        Configure::load($path2, true);
        $this->assertArrayHasKey('Plugin', Configure::get());
    }

    #[DataProvider('writeProvider')]
    public function testWriteMerge($key, $data)
    {
        Configure::write($key, $data);
        $this->assertArrayHasKey('Config', Configure::get());
    }

    #[DataProvider('writeProvider')]
    #[RunInSeparateProcess]
    public function testWriteNoMerge($key, $data)
    {
        Configure::load('/hjhj/tetet.php');
        Configure::write($key, $data);
        $this->assertArrayHasKey('Config', Configure::get());
    }

    public static function writeProvider()
    {
        return [
            ['Config.version', '1.2'],
            ['Config.Cache', ['path' => '/cache', 'name' => 'test']],
        ];
    }
}
