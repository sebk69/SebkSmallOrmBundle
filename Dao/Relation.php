<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigtht 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Dao;

use Sebk\SmallOrmBundle\Factory\Dao;

/**
 * Relation to another model
 */
class Relation
{
    protected $dao;
    protected $keys;

    /**
     * Contruct relation
     * @param string $modelBundle
     * @param string $modelName
     * @param string $alias
     * @param Dao $daoFactory
     */
    public function __construct($modelBundle, $modelName, $relationKeys, Dao $daoFactory)
    {
        $this->dao   = $daoFactory->get($modelBundle, $modelName);
        $this->keys = $relationKeys;
    }

    /**
     * @return Dao
     */
    public function getDao()
    {
        return $this->dao;
    }
}