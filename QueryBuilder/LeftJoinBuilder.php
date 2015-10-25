<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigtht 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\QueryBuilder;

/**
 *
 */
class LeftJoinBuilder extends JoinBuilder
{
    /**
     *
     * @param null $type
     * @return string
     */
    public function getSql($type = null)
    {
        return parent::getSql("LEFT JOIN");
    }
}