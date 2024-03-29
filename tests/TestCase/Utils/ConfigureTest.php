<?php

namespace CAMOO\Test\TestCase\Utils;

use CAMOO\Utils\Configure;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigureTest
 *
 * @author CamooSarl
 *
 * @covers \CAMOO\Utils\Configure
 */
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

    /**
     * @covers \CAMOO\Utils\Configure::load
     *
     * @testWith        ["/tmp/test_configure1.php"]
     */
    public function testInstance($path)
    {
        $this->assertNull(Configure::load($path));
    }

    /**
     * @covers \CAMOO\Utils\Configure::read
     *
     * @depends testInstance
     */
    public function testRead()
    {
        $this->assertNull(Configure::read('test.test'));
        $this->assertIsArray(Configure::read('Config'));
    }

    /**
     * @covers \CAMOO\Utils\Configure::check
     *
     * @depends testInstance
     */
    public function testCheck()
    {
        $this->assertTrue(Configure::check('Config'));
        $this->assertFalse(Configure::check('epepep'));
    }

    /**
     * @covers \CAMOO\Utils\Configure::get
     *
     * @depends testInstance
     */
    public function testGet()
    {
        $this->assertIsArray(Configure::get());
    }

    /**
     * @covers \CAMOO\Utils\Configure::load
     *
     * @testWith        ["/tmp/test_configure2.php"]
     */
    public function testMerge($path2)
    {
        Configure::load($path2, true);
        $this->assertArrayHasKey('Plugin', Configure::get());
    }

    /**
     * @covers \CAMOO\Utils\Configure::write
     *
     * @dataProvider writeProvider
     */
    public function testWriteMerge($key, $data)
    {
        Configure::write($key, $data);
        $this->assertArrayHasKey('Config', Configure::get());
    }

    /**
     * @covers \CAMOO\Utils\Configure::write
     *
     * @dataProvider writeProvider
     *
     * @runInSeparateProcess
     */
    public function testWriteNoMerge($key, $data)
    {
        Configure::load('/hjhj/tetet.php');
        Configure::write($key, $data);
        $this->assertArrayHasKey('Config', Configure::get());
    }

    public function writeProvider()
    {
        return [
            ['Config.version', '1.2'],
            ['Config.Cache', ['path' => '/cache', 'name' => 'test']],
        ];
    }
}
