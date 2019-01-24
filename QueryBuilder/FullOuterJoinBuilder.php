<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrightt 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\QueryBuilder;

/**
 *
 */
class FullOuterJoinBuilder extends JoinBuilder
{

    /**
     *
     * @param null $type
     * @return string
     * @throws QueryBuilderException
     */
    public function getSql($type = null)
    {
        throw new QueryBuilderException("Full outer join is not now implemented");

        return parent::getSql("FULL OUTER JOIN");
    }
}