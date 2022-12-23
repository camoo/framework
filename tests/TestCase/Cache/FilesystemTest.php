<?php

namespace CAMOO\Test\TestCase\Cache;

use CAMOO\Cache\Filesystem;
use CAMOO\Cache\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class FilesystemTest
 *
 * @author CamooSarl
 *
 * @covers \CAMOO\Cache\Filesystem
 */
class FilesystemTest extends TestCase
{
    private $oCache;

    public function setUp(): void
    {
        $this->oCache = new Filesystem();
        if (!file_exists('/tmp/cache/persistent/core')) {
            mkdir('/tmp/cache/persistent/core', 0777, true);
        }
    }

    public function tearDown(): void
    {
        unset($this->oCache);
        @rmdir('/tmp/cache/persistent/core');
        @rmdir('/tmp/cache/persistent');
        @rmdir('/tmp/cache');
    }

    /** @covers \CAMOO\Cache\Filesystem::clear */
    public function testInstance()
    {
        $this->assertInstanceOf(Filesystem::class, $this->oCache);
        $this->assertIsBool($this->oCache->clear());
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::set
     *
     * @dataProvider setCacheProvider
     *
     * @depends testInstance
     */
    public function testSetsuccess($key, $value, $ttl)
    {
        $set = $this->oCache->set($key, $value, $ttl);
        $this->assertTrue($set);
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::set
     *
     * @dataProvider setCacheProvider
     *
     * @depends testInstance
     */
    public function testSetTwice($key, $value, $ttl)
    {
        $set = $this->oCache->set($key, $value, $ttl);
        $this->assertNull($set);
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::get
     *
     * @dataProvider setCacheProvider
     *
     * @depends testInstance
     */
    public function testGetsuccess($key, $value, $ttl)
    {
        $get = $this->oCache->get($key);
        $this->assertEquals($get, $value);
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::delete
     *
     * @dataProvider setCacheProvider
     *
     * @depends testInstance
     */
    public function testDeletesuccess($key, $value, $ttl)
    {
        $this->assertTrue($this->oCache->delete($key));
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::set
     *
     * @dataProvider setCacheProviderFailure
     *
     * @depends testInstance
     */
    public function testSetFailure1($key, $value, $ttl)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->oCache->set($key, $value, $ttl);
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::get
     *
     * @dataProvider setCacheProviderFailure
     *
     * @depends testInstance
     */
    public function testGetFailure1($key, $value, $ttl)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->oCache->get($key);
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::get
     *
     * @depends testInstance
     *
     * @testWith        ["test", 4]
     *                  ["longer-string", 13]
     *                  ["null-string"]
     *
     * @param mixed|null $default
     */
    public function testGetFailureDefault($key, $default = null)
    {
        $this->assertEquals($default, $this->oCache->get($key, $default));
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::delete
     *
     * @dataProvider setCacheProviderFailure
     *
     * @depends testInstance
     */
    public function testDeleteFailure1($key, $value, $ttl)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->oCache->delete($key);
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::setMultiple
     *
     * @dataProvider setCacheProviderMultiple
     *
     * @depends testInstance
     */
    public function testSetMultiple($values)
    {
        $this->assertTrue($this->oCache->setMultiple($values));
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::getMultiple
     *
     * @dataProvider setCacheProviderMultiple
     *
     * @depends testInstance
     */
    public function testGetMultipleSucces($values)
    {
        $this->assertEquals($values, $this->oCache->getMultiple(array_keys($values)));
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::getMultiple
     *
     * @dataProvider setCacheProviderMultiple
     *
     * @depends testInstance
     */
    public function testDeleteMultipleSucces($values)
    {
        $this->assertTrue($this->oCache->deleteMultiple(array_keys($values)));
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::getMultiple
     *
     * @dataProvider setCacheProviderMultipleFailure
     *
     * @depends testInstance
     */
    public function testGetMultipleFailure($values)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->oCache->getMultiple(array_keys($values));
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::setMultiple
     *
     * @dataProvider setCacheProviderMultipleFailure
     *
     * @depends testInstance
     */
    public function testSetMultipleFailure($values)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->oCache->setMultiple($values);
    }

    /**
     * @covers \CAMOO\Cache\Filesystem::setMultiple
     *
     * @dataProvider setCacheProviderMultipleFailure
     *
     * @depends testInstance
     */
    public function testDeleteMultipleFailure($values)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->oCache->deleteMultiple(array_keys($values));
    }

    public function setCacheProviderMultipleFailure()
    {
        return [
            [[1 => 'dar', null => (object)['bar']]],
        ];
    }

    public function setCacheProviderMultiple()
    {
        return [
            [['doo' => 'dar', 'foo' => (object)['bar']]],
            [['dood' => 123, 'food' => serialize(['bar'])]],
        ];
    }

    public function setCacheProvider()
    {
        return [
            ['test', 'top', 0],
            ['baaf', new \stdClass(), 30],
        ];
    }

    public function setCacheProviderFailure()
    {
        return [
            [1, 'ddk', 0],
            [null, new \stdClass(), 30],
            [['a'], new \stdClass(), 30],
            [new \stdClass(), new \stdClass(), 30],
            [(object)['b'], 'Myvalue', 30],
        ];
    }
}
