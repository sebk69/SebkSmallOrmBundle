<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigth 2015 - Sébastien Kus
 */

namespace Sebk\SmallOrmBundle\Database;

/**
 * Connection to database
 * TODO: need to be generalized when ORM finished
 */
class Connection
{
    protected $pdo;

    /**
     * Construct and open connection
     * @param string $dbType
     * @param string $host
     * @param string $database
     * @param string $user
     * @param string $password
     * @throws ConnectionException
     */
    public function __construct($dbType, $host, $database, $user, $password)
    {
        switch($dbType) {
            case "mysql":
                $connectionString = "mysql:dbname=$database;host=$host";
                break;

            default:
                throw new ConnectionException("Database type is not developped for now");
        }

        try {
            $this->pdo = new \PDO($connectionString, $user, $password);
        } catch(\PDOException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * Execute sql instruction
     * @param string $sql
     * @param array $params
     * @return array
     * @throws ConnectionException
     */
    public function execute($sql, $params = array()) {
        $statement = $this->pdo->prepare($sql);

        foreach($params as $param => $value) {
            $statement->bindParam($param, $value);
        }
        
        if($statement->execute()) {
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $errInfo = $statement->errorInfo();
            throw new ConnectionException("Fail to exectue request : SQLSTATE[".$errInfo[0]."][".$errInfo[1]."] ".$errInfo[2]);
        }
    }

    /**
     * Get last insert id
     * @return int
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}