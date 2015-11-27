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
class Model implements \JsonSerializable {

    private $modelName;
    private $bundle;
    protected $container;
    private $primaryKeys = array();
    private $originalPrimaryKeys = null;
    private $fields = array();
    private $toOnes = array();
    private $toManys = array();
    private $metadata = array();
    public $fromDb = false;
    public $altered = false;

    /**
     * Construct model
     * @param string $modelName
     * @param array $primaryKeys
     * @param array $fields
     */
    public function __construct($modelName, $bundle, $primaryKeys, $fields, $toOnes, $toManys, $container) {
        $this->modelName = $modelName;
        $this->bundle = $bundle;
        $this->container = $container;

        foreach ($primaryKeys as $primaryKey) {
            $this->primaryKeys[$primaryKey] = null;
        }

        foreach ($fields as $field) {
            $this->fields[$field] = null;
        }

        foreach ($toOnes as $toOne) {
            $this->toOnes[$toOne] = null;
        }

        foreach ($toManys as $toMany) {
            $this->toManys[$toMany] = null;
        }
    }

    /**
     * @return string
     */
    public function getModelName() {
        return $this->modelName;
    }

    /**
     * @return string
     */
    public function getBundle() {
        return $this->bundle;
    }

    /**
     * Magic method to access getters and setters
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \ModelException
     */
    public function __call($method, $args) {
        $type = substr($method, 0, 3);
        $name = lcfirst(substr($method, 3));
        $typeField = $this->getFieldType($name);

        switch ($type) {
            case "get":
                if ($typeField == "primaryKeys") {
                    return $this->primaryKeys[$name];
                } elseif ($typeField == "field") {
                    return $this->fields[$name];
                } elseif ($typeField == "toOne") {
                    return $this->toOnes[$name];
                } elseif ($typeField == "toMany") {
                    return $this->toManys[$name];
                } elseif ($typeField == "metadata" && array_key_exists($name, $this->metadata)) {
                    return $this->metadata[$name];
                }
                break;
            case "set":
                if ($typeField == "primaryKeys") {
                    $this->primaryKeys[$name] = $args[0];
                } elseif ($typeField == "field") {
                    $this->fields[$name] = $args[0];
                } elseif ($typeField == "toOne") {
                    $this->toOnes[$name] = $args[0];
                } elseif ($typeField == "toMany") {
                    $this->toManys[$name] = $args[0];
                } elseif ($typeField == "metadata") {
                    $this->metadata[$name] = $args[0];
                }
                return $this;
                break;
            default:
                throw new ModelException("Method '$method' doesn't extist in model '$this->modelName' of bundle '$this->bundle'");
        }
    }

    public function setOriginalPrimaryKeys() {
        $this->originalPrimaryKeys = $this->primaryKeys;
    }

    public function getOriginalPrimaryKeys() {
        return $this->originalPrimaryKeys;
    }

    /**
     * Get field type
     * @param string $field
     * @return string
     * @throws \ModelException
     */
    public function getFieldType($field) {
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

        return "metadata";
    }

    /**
     *
     * @return array
     */
    public function getPrimaryKeys() {
        return $this->primaryKeys;
    }

    /**
     *
     * @param boolean $dependecies
     * @return array
     */
    public function toArray($dependecies = true, $onlyFields = false) {
        $result = array();

        foreach ($this->primaryKeys as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            } else {
                $result[$key] = null;
            }
        }

        foreach ($this->fields as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            } else {
                $result[$key] = null;
            }
        }

        if ($dependecies) {
            foreach ($this->toOnes as $key => $model) {
                if ($model !== null) {
                    $result[$key] = $model->jsonSerialize();
                } else {
                    $result[$key] = null;
                }
            }

            foreach ($this->toManys as $key => $array) {
                if ($array !== null) {
                    $result[$key] = array();
                    foreach ($array as $i => $model) {
                        if ($model !== null && $model instanceof Model) {
                            $result[$key][] = $model->jsonSerialize();
                        } elseif ($model !== null) {
                            $result[$key][] = $model;
                        } else {
                            $result[$key][] = null;
                        }
                    }
                } else {
                    $result[$key] = array();
                }
            }

            foreach ($this->metadata as $key => $value) {
                if ($value instanceof ModelCollection || $value instanceof Model) {
                    $result[$key] = $value->toArray();
                } else {
                    $result[$key] = $value;
                }
            }

            if (!$onlyFields) {
                $result["fromDb"] = $this->fromDb;
            }
        }

        return $result;
    }

    /**
     * 
     * @return array
     */
    public function jsonSerialize() {
        if (is_array($this->toArray())) {
            return $this->toUtf8Array($this->toArray());
        } else {
            return $this->toArray();
        }
    }

    /**
     * 
     * @param array $array
     * @return array
     */
    protected function toUtf8Array($array) {
        foreach ($array as $key => $cell) {
            if (is_array($cell)) {
                $array[$key] = $this->toUtf8Array($cell);
            } elseif (!is_object($cell)) {
                $array[$key] = $this->toUtf8String($cell);
            } elseif ($cell instanceof Model) {
                $array[$key] = $this->toUtf8Array($cell->toArray());
            } else {
                $array[$key] = $this->toUtf8Array((array) $cell);
            }
        }

        return $array;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function toUtf8String($str) {
        if (mb_detect_encoding($str, 'UTF-8', true) === false) {
            return utf8_encode($str);
        }

        return $str;
    }

    /**
     * Load a toOne relation if not loaded
     * @param type $alias
     * @throws DaoException
     */
    protected function loadToOne($alias, $dependenciesAliases) {
        if (!array_key_exists($alias, $this->toOnes)) {
            throw new DaoException("Field '$alias' does not exists (loading to one relation");
        }

        if ($this->toOnes[$alias] === null) {
            $this->container
                    ->get("sebk_small_orm_dao")
                    ->get($this->bundle, $this->modelName)
                    ->loadToOne($alias, $this, $dependenciesAliases);
        }
    }
    
    /**
     * Load a toMany relation if not loaded
     * @param type $alias
     * @throws DaoException
     */
    protected function loadToMany($alias, $dependenciesAliases) {
        if (!array_key_exists($alias, $this->toManys)) {
            throw new DaoException("Field '$alias' does not exists (loading to one relation");
        }

        if (count($this->toManys[$alias])) {
            $this->container
                    ->get("sebk_small_orm_dao")
                    ->get($this->bundle, $this->modelName)
                    ->loadToMany($alias, $this, $dependenciesAliases);
        }
    }
}
