<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigth 2015 - Sébastien Kus
 */

namespace Sebk\SmallOrmBundle\Dao;

use Sebk\SmallOrmBundle\Database\Connection;
use Sebk\SmallOrmBundle\QueryBuilder\QueryBuilder;

/**
 * Abstract class to provide base dao features
 */
abstract class AbstractDao
{
    protected $connection;
    protected $modelNamespace;
    private $modelName;
    private $dbTableName;
    private $primaryKeys;
    private $fields;

    public function __construct(Connection $connection, $modelNamespace)
    {
        $this->connection     = $connection;
        $this->modelNamespace = $modelNamespace;
        $this->build();
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * @param string $name
     * @return \Sebk\SmallOrmBundle\Database\AbstractDao
     */
    protected function setModelName($name)
    {
        $this->modelName = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbTableName()
    {
        return $this->dbTableName;
    }

    /**
     * @param string $name
     * @return \Sebk\SmallOrmBundle\Database\AbstractDao
     */
    protected function setDbTableName($name)
    {
        $this->dbTableName = $name;

        return $this;
    }

    /**
     * @param string $dbFieldName
     * @param string $modelFieldName
     */
    protected function addPrimaryKey($dbFieldName, $modelFieldName)
    {
        $this->primaryKeys[] = new Field($dbFieldName, $modelFieldName);
    }

    /**
     * @param string $dbFieldName
     * @param string $modelFieldName
     */
    protected function addField($dbFieldName, $modelFieldName)
    {
        $this->fields[] = new Field($dbFieldName, $modelFieldName);
    }

    /**
     * Build definition of table
     * Must be defined in model acheviment
     */
    abstract protected function build();

    /**
     * Get primary keys definition
     * @return array
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * Get fields difinitions
     * @param boolean $withIds
     * @return array
     */
    public function getFields($withIds = true)
    {
        $result = array();
        if ($withIds == true) {
            $result = $this->primaryKeys;
        }

        $result = array_merge($result, $this->fields);

        return $result;
    }

    /**
     * Create new model
     * @return \Sebk\SmallOrmBundle\Dao\modelClass
     */
    public function newModel()
    {
        $modelClass = $this->modelNamespace."\\".$this->modelName;

        $primaryKeys = array();
        foreach ($this->primaryKeys as $primaryKey) {
            $primaryKeys[] = strtolower($primaryKey->getModelName());
        }

        $fields = array();
        foreach ($this->fields as $field) {
            $fields[] = strtolower($field->getModelName());
        }

        return new $modelClass($this->modelName, $primaryKeys, $fields);
    }

    /**
     * Create query builder object with base model from this dao
     * @param type $alias
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null)
    {
        return new QueryBuilder($this, $alias);
    }

    /**
     * Execute sql and get raw result
     * @param QueryBuilder $query
     * @return array
     */
    public function getRawResult(QueryBuilder $query)
    {
        return $this->connection->execute($query->getSql(), $query->getParameters());
    }

    /**
     * Has field
     * @param string $fieldName
     * @return boolean
     */
    public function hasField($fieldName)
    {
        foreach($this->getFields() as $field) {
            if($field->getModelName($field)) {
                return true;
            }
        }

        return false;
    }

    public function getField($fieldName)
    {
        foreach($this->getFields() as $field) {
            if($field->getModelName() == $fieldName) {
                return $field;
            }
        }

        throw new DaoException("Field '$fieldName' not found");
    }
}