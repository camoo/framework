<?php

namespace CAMOO\Test\TestCase\File;

use PHPUnit\Framework\TestCase;
use \CAMOO\File\Json;
use PHPUnit\Framework\Error\Error;

/**
 * Class JsonTest
 * @author CamooSarl
 * @covers \CAMOO\File\Json
 */
class JsonTest extends TestCase
{
    private $oJson;

    public function setUp() : void
    {
        $this->oJson = new Json;
        file_put_contents('/tmp/test_success.json', '{"test": "OK"}');
        file_put_contents('/tmp/test_failure.json', '"test": "OK"');
    }

    public function tearDown() : void
    {
        unlink('/tmp/test_success.json');
        unlink('/tmp/test_failure.json');
        unset($this->oJson);
    }

    /**
     * @covers \CAMOO\File\Json::read
	 * @testWith        ["/tmp/test_success.json"]
     */
    public function testReadSuccess($sFile)
    {
        $this->assertIsArray($this->oJson->read($sFile));
    }

    /**
     * @covers \CAMOO\File\Json::read
	 * @testWith        ["/tmp/test_error.json"]
     */
    public function testReadError($sFile)
    {
		$this->expectException(Error::class);
        $this->oJson->read($sFile);
    }

    /**
     * @covers \CAMOO\File\Json::read
	 * @testWith        ["/tmp/test_failure.json"]
     */
    public function testReadFailure($sFile)
    {
        $this->assertNull($this->oJson->read($sFile));
    }

}
