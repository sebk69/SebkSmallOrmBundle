<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigth 2015 - Sébastien Kus
 */

namespace Sebk\SmallOrmBundle\Dao;

use Sebk\SmallOrmBundle\Database\Connection;

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

    public function __construct(Connection $connection, $modelNamespace) {
        $this->connection = $connection;
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

    abstract protected function build();

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
}