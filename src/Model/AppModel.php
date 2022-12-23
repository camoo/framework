<?php

namespace CAMOO\Model;

use CAMOO\Utils\Configure;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class AppModel
 */
class AppModel
{
    protected $conn = null;

    protected $db = 'default';

    protected $driver = 'pdo_mysql';

    protected $queryBuilder = null;

    /**
     * @return Doctrine\DBAL\Connection
     *
     * @source vendor/doctrine/dbal/lib/Doctrine/DBAL/Connection.php
     */
    public function getConnection(): Connection
    {
        if ($this->conn === null) {
            $connectionParams = Configure::read('Database.' . $this->db);
            $connectionParams['driver'] = $this->driver;
            $connectionParams['user'] = $connectionParams['username'];
            $this->conn = DriverManager::getConnection($connectionParams, new Configuration());
        }

        return $this->conn;
    }

    public function setDB($sDB): void
    {
        $this->db = $sDB;
    }

    public function setDriver($driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Gets doctrine queryBuilder
     *
     * @doc https://www.doctrine-project.org/projects/doctrine-dbal/en/2.9/reference/query-builder.html#sql-query-builder
     *
     * @return Doctrine\DBAL\Query\QueryBuilder
     */
    public function queryBuilder(): QueryBuilder
    {
        return $this->conn->createQueryBuilder();
    }
}
