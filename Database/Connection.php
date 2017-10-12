<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Database;

/**
 * Connection to database
 * TODO: need to be generalized when ORM finished
 */
class Connection
{
    protected $pdo;
    protected $database;
    protected $transactionInUse = false;

    /**
     * Construct and open connection
     * @param string $dbType
     * @param string $host
     * @param string $database
     * @param string $user
     * @param string $password
     * @throws ConnectionException
     */
    public function __construct($dbType, $host, $database, $user, $password, $encoding)
    {
        $this->database = $database;

        switch ($dbType) {
            case "mysql":
                // Connect to database
                $connectionString = "mysql:dbname=$database;host=$host;charset=$encoding";
                try {
                    $this->pdo = new \PDO($connectionString, $user, $password);
                } catch (\PDOException $e) {
                    // Create database if not exists
                    $connectionString = "mysql:host=$host;charset=$encoding";
                    try {
                        $this->pdo = new \PDO($connectionString, $user, $password);
                    } catch (\PDOException $e) {
                        throw new ConnectionException($e->getMessage());
                    }
                    $this->execute("create database `$database`;use `$database`;");
                }
                break;

            default:
                throw new ConnectionException("Database type is not developped for now");
        }
    }

    /**
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Execute sql instruction
     * @param string $sql
     * @param array $params
     * @return array
     * @throws ConnectionException
     */
    public function execute($sql, $params = array())
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($params as $param => $value) {
            $statement->bindValue(":".$param, $value);
        }
        if ($statement->execute()) {
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $errInfo = $statement->errorInfo();
            throw new ConnectionException("Fail to exectue request : SQLSTATE[".$errInfo[0]."][".$errInfo[1]."] ".$errInfo[2]);
        }
    }

    /**
     * Start transaction
     * @return $this
     * @throws TransactionException
     */
    public function startTransaction()
    {
        if($this->getTransactionInUse()) {
            throw new TransactionException("Transaction already started");
        }

        $this->execute("START TRANSACTION");
        $this->transactionInUse = true;

        return $this;
    }

    /**
     * Return true if transaction in use
     * @return bool
     */
    public function getTransactionInUse()
    {
        return $this->transactionInUse;
    }

    /**
     * Commit transaction
     * @return $this
     * @throws TransactionException
     */
    public function commit()
    {
        if(!$this->getTransactionInUse()) {
            throw new TransactionException("Transaction not started");
        }

        $this->execute("COMMIT");

        $this->transactionInUse = false;

        return $this;
    }

    /**
     * Rollback transaction
     * @return $this
     * @throws TransactionException
     */
    public function rollback()
    {
        if(!$this->getTransactionInUse()) {
            throw new TransactionException("Transaction not started");
        }

        $this->execute("ROLLBACK");

        $this->transactionInUse = false;

        return $this;
    }

    /**
     * Get last insert id
     * @return int
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}