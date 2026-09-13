<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Model;

use CAMOO\Model\AppModel;
use CAMOO\Utils\Configure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppModel::class)]
class AppModelTest extends TestCase
{
    public function setUp(): void
    {
        Configure::write('Database.default', [
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'username' => 'root',
        ]);
        Configure::write('Database.custom_db', [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }

    public function testAppModelConnectionAndQueryBuilder(): void
    {
        $model = new AppModel();
        $conn = $model->getConnection();
        $this->assertInstanceOf(Connection::class, $conn);
        $this->assertSame($conn, $model->getConnection());

        $builder = $model->queryBuilder();
        $this->assertInstanceOf(QueryBuilder::class, $builder);
        $this->assertInstanceOf(QueryBuilder::class, $model->getQueryBuilder());
    }

    public function testSetDBAndDriver(): void
    {
        $model = new AppModel();
        $model->setDB('custom_db');
        $model->setDriver('pdo_sqlite');
        $conn = $model->getConnection();
        $this->assertInstanceOf(Connection::class, $conn);
    }
}
