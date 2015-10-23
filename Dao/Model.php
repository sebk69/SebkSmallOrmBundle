<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Dao;

/**
 * Class model
 */
class Model
{
    private $modelName;
    private $bundle;
    private $primaryKeys = array();
    private $fields      = array();
    private $toOnes      = array();
    private $toManys     = array();
    private $fromDb      = false;
    private $altered     = false;

    /**
     * Construct model
     * @param string $modelName
     * @param array $primaryKeys
     * @param array $fields
     */
    public function __construct($modelName, $bundle, $primaryKeys, $fields, $toOnes, $toManys)
    {
        $this->modelName = $modelName;
        $this->bundle = $bundle;

        foreach ($primaryKeys as $primaryKey) {
            $this->primaryKeys[$primaryKey] = null;
        }
        
        foreach ($fields as $field) {
            $this->fields[$field] = null;
        }

        foreach($toOnes as $toOne) {
            $this->toOnes[$toOne] = null;
        }

        foreach($toManys as $toMany) {
            $this->toManys[$toMany] = null;
        }
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Magic method to access getters and setters
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \ModelException
     */
    public function __call($method, $args)
    {
        $type = substr($method, 0, 3);
        $name = strtolower(substr($method, 3));
        $typeField = $this->getFieldType($name);

        switch ($type) {
            case "get":
                if($typeField == "primaryKeys") {
                    return $this->primaryKeys[$name];
                } elseif($typeField == "field") {
                    return $this->fields[$name];
                } elseif($typeField == "toOne") {
                    return $this->toOnes[$name];
                } elseif($typeField == "toMany") {
                    return $this->toManys[$name];
                }
                break;
            case "set":
                if($typeField == "primaryKeys") {
                    $this->primaryKeys[$name] = $args[0];
                } elseif($typeField == "field") {
                    $this->fields[$name] = $args[0];
                } elseif($typeField == "toOne") {
                    $this->toOnes[$name] = $args[0];
                } elseif($typeField == "toMany") {
                    $this->toManys[$name] = $args[0];
                }
                return $this;
                break;
            default:
                throw new ModelException("Method '$method' doesn't extist in model '$this->modelName' of bundle '$this->bundle'");
        }
    }

    /**
     * Get field type
     * @param string $field
     * @return string
     * @throws \ModelException
     */
    public function getFieldType($field)
    {
        if (array_key_exists($field, $this->primaryKeys)) {
            return "primaryKeys";
        }

        if (array_key_exists($field, $this->fields)) {
            return "field";
        }

        if (array_key_exists($field, $this->toOnes)) {
            return "toOne";
        }
        
        if (array_key_exists($field, $this->toManys)) {
            return "toMany";
        }

        throw new ModelException("Field '$field' doesn't exists in model '$this->modelName'");
    }
}