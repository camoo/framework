<?php

namespace CAMOO\Test\TestCase\Utils;

use CAMOO\Utils\QueryData;
use PHPUnit\Framework\TestCase;

/**
 * Class QueryDataTest
 *
 * @author CamooSarl
 *
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
