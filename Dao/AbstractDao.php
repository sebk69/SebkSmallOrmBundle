<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
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
    private $modelBundle;
    private $dbTableName;
    private $primaryKeys;
    private $fields;
    private $toOne  = array();
    private $toMany = array();

    public function __construct(Connection $connection, $modelNamespace, $modelName, $modelBundle)
    {
        $this->connection     = $connection;
        $this->modelNamespace = $modelNamespace;
        $this->modelName = $modelName;
        $this->modelBundle = $modelBundle;
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

        return $this;
    }

    /**
     * @param string $dbFieldName
     * @param string $modelFieldName
     */
    protected function addField($dbFieldName, $modelFieldName)
    {
        $this->fields[] = new Field($dbFieldName, $modelFieldName);

        return $this;
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

        return new $modelClass($this->modelName, $this->modelBundle, $primaryKeys, $fields);
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
        return $this->connection->execute($query->getSql(),
                $query->getParameters());
    }

    /**
     * Has field
     * @param string $fieldName
     * @return boolean
     */
    public function hasField($fieldName)
    {
        foreach ($this->getFields() as $field) {
            if ($field->getModelName($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get field object
     * @param string $fieldName
     * @return Field
     * @throws DaoException
     */
    public function getField($fieldName)
    {
        foreach ($this->getFields() as $field) {
            if ($field->getModelName() == $fieldName) {
                return $field;
            }
        }

        throw new DaoException("Field '$fieldName' not found");
    }

    /**
     * Add a relation to model
     * @param \Sebk\SmallOrmBundle\Dao\Relation $relation
     * @return \Sebk\SmallOrmBundle\Dao\AbstractDao
     * @throws DaoException
     */
    public function addRelation(Relation $relation)
    {
        if ($relation instanceof ToOneRelation) {
            $this->toOne[$relation->getAlias()] = $relation;

            return $this;
        }

        if ($relation instanceof ToManyRelation) {
            $this->toMany[$relation->getAlias()] = $relation;

            return $this;
        }

        throw new DaoException("Unknonw relation type");
    }

    /**
     * Get result for a query
     * @param QueryBuilder $query
     * @return array
     */
    public function getResult(QueryBuilder $query)
    {
        $records = $this->getRawResult($query);
        
        return $this->buildResult($query, $records);
    }

    /**
     * Convert resultset to objects
     * @param QueryBuilder $query
     * @param array $records
     * @param string $alias
     * @return array
     */
    protected function buildResult(QueryBuilder $query, $records, $alias = null)
    {
        $result = array();

        $group = array();
        foreach ($records as $record) {
            $ids = $this->extractPrimaryKeysOfRecord($query, $alias, $record);
            foreach ($ids as $idName => $idValue) {
                if (count($group) && $savedIds[$idName] != $idValue) {
                    $result[] = $this->populate($query, $alias, $group);
                    $group    = array();
                }

                $group[] = $record;
            }

            $savedIds = $ids;
        }
        if (count($group)) {
            $result[] = $this->populate($query, $alias, $group);
        }

        return $result;
    }

    /**
     *
     * @param QueryBuilder $query
     * @param string $alias
     * @param array $records
     * @return Model
     */
    protected function populate(QueryBuilder $query, $alias, $records)
    {
        $model = $this->newModel();
        $fields = $this->extractFieldsOfRecord($query, $alias, $records[0]);

        foreach($fields as $property => $value) {
            $method = "set".$property;
            $model->$method($value);
        }

        return $model;
    }

    /**
     * Extract ids of this model of record
     * @param QueryBuilder $query
     * @param string $alias
     * @param array $record
     * @return array
     * @throws DaoException
     */
    private function extractPrimaryKeysOfRecord(QueryBuilder $query, $alias, $record)
    {
        $queryRelation = $query->getRelation($alias);

        $result = array();
        foreach ($this->getPrimaryKeys() as $field) {
            if (array_key_exists($queryRelation->getFieldAliasForSql($field), $record)) {
                $result[$field->getModelName()] = $record[$queryRelation->getFieldAliasForSql($field)];
            } else {
                throw new DaoException("Record not match query");
            }
        }

        return $result;
    }

    /**
     * Extract fields of this model of record
     * @param QueryBuilder $query
     * @param string $alias
     * @param array $record
     * @return array
     * @throws DaoException
     */
    private function extractFieldsOfRecord(QueryBuilder $query, $alias, $record)
    {
        $queryRelation = $query->getRelation($alias);

        $result = array();
        foreach ($this->getFields() as $field) {
            if (array_key_exists($queryRelation->getFieldAliasForSql($field), $record)) {
                $result[$field->getModelName()] = $record[$queryRelation->getFieldAliasForSql($field)];
            } else {
                throw new DaoException("Record not match query");
            }
        }

        return $result;
    }
}