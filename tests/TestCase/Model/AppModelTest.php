<?php

namespace CAMOO\Test\TestCase\Model;

use PHPUnit\Framework\TestCase;
use \CAMOO\Model\AppModel;
use \CAMOO\Utils\Configure;

/**
 * Class AppModelTest
 * @author CamooSarl
 * @covers \CAMOO\Model\AppModel
 */
class AppModelTest extends TestCase
{
    private $oModel;
    private $hDBConfig = [
             'database' => 'cm_test',
             'user' => 'travis',
             'password' => '',
             'host' => '127.0.0.1',
     ];

    public function setUp() : void
    {
        Configure::write('Database.test', $this->hDBConfig);
        $this->oModel = new AppModel;
    }

    public function testInstance()
    {
        $this->assertInstanceOf(AppModel::class, $this->oModel);
    }

    /**
     * @covers \CAMOO\Model\AppModel::getConnection
     * @depends testInstance
     */
    public function testGetConnection()
    {
        $this->oModel->setDB('test');
        $db = $this->oModel->getConnection();
        $db->connect();
        $this->assertTrue($db->isConnected());
    }
}
