<?php
namespace CAMOO\Model;

use \Doctrine\DBAL\Configuration;
use \Doctrine\DBAL\DriverManager;
use CAMOO\Utils\Configure;

/**
*https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html#sql-query-builder
*/
class AppModel
{
    protected $conn = null;
    protected $db = 'default';
    protected $driver = 'pdo_mysql';
    protected $queryBuilder = null;

    public function __construct()
    {
        if ($this->conn === null) {
            $connectionParams = Configure::read('Database.' .$this->db);
            $connectionParams['driver'] = $this->driver;
            $this->conn = DriverManager::getConnection($connectionParams, Configuration());
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function setDB($sDB)
    {
        return $this->db = $sDB;
    }

    public function getQueryBuilder()
    {
        return $this->conn->createQueryBuilder();
    }
}
