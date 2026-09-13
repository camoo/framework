<?php

namespace CAMOO\Test\TestCase\File;

use CAMOO\Exception\Exception;
use CAMOO\File\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Json::class)]
class JsonTest extends TestCase
{
    public function testDecodeValidJson(): void
    {
        $json = new Json(null, '{"key":"value"}');
        $result = $json->decode(bAsHash: true);
        $this->assertSame(['key' => 'value'], $result);
    }

    public function testDecodeNullThrowsException(): void
    {
        $json = new Json();
        $this->expectException(Exception::class);
        $json->decode();
    }

    public function testDecodeInvalidJsonThrowsException(): void
    {
        $json = new Json(null, '{invalid_json}');
        $this->expectException(Exception::class);
        $json->decode();
    }

    public function testReadValidJsonFile(): void
    {
        $file = TMP . 'test_' . uniqid() . '.json';
        file_put_contents($file, json_encode(['foo' => 'bar']));

        $json = new Json($file);
        $result = $json->read();
        $this->assertSame(['foo' => 'bar'], $result);

        @unlink($file);
    }

    public function testReadNonExistentFileThrowsException(): void
    {
        $json = new Json(TMP . 'non_existent_file_xyz.json');
        $this->expectException(Exception::class);
        $json->read();
    }
}
