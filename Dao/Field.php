<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Dao;

/**
 * Model field
 */
class Field
{
    protected $dbName;
    protected $modelName;

    /**
     *
     * @param string $dbName
     * @param string $modelName
     */
    public function __construct($dbName, $modelName)
    {
        $this->dbName = $dbName;
        $this->modelName = $modelName;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return "`".$this->dbName."`";
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }
}