<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigth 2015 - Sébastien Kus
 */

namespace Sebk\SmallOrmBundle\Dao;

/**
 * Class model
 */
class Model
{
    private $modelName;
    private $primaryKeys = array();
    private $fields      = array();
    private $fromDb      = false;
    private $altered     = false;

    /**
     * Construct model
     * @param string $modelName
     * @param array $primaryKeys
     * @param array $fields
     */
    public function __construct($modelName, $primaryKeys, $fields)
    {
        $this->modelName = $modelName;

        foreach ($primaryKeys as $primaryKey) {
            $this->primaryKeys[$primaryKey] = null;
        }
        
        foreach ($fields as $field) {
            $this->fields[$field] = null;
        }
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
                } else {
                    return $this->fields[$name];
                }
                break;
            case "set":
                if($typeField == "primaryKeys") {
                    $this->primaryKeys[$name] = $args[0];
                } else {
                    $this->fields[$name] = $args[0];
                }
                return $this;
                break;
            default:
                throw new ModelException("Method '$method' doesn't extist in model '$this->modelName'");
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
            return "fields";
        }

        throw new ModelException("Field '$field' doesn't exists in model '$this->modelName'");
    }
}