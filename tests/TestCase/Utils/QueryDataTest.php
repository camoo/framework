<?php

namespace CAMOO\Test\TestCase\Utils;

use PHPUnit\Framework\TestCase;
use \CAMOO\Utils\QueryData;

/**
 * Class QueryDataTest
 * @author CamooSarl
 * @covers \CAMOO\Utils\QueryData
 */
class QueryDataTest extends TestCase
{
    public function testInstance()
    {
        $query = new QueryData(['test' => true]);
        $this->assertInstanceOf(QueryData::class, $query);
        $this->assertEquals(true, $query->test);
    }
}
