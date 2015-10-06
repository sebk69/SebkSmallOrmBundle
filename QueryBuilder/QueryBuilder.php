<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigth 2015 - Sébastien Kus
 */

namespace Sebk\SmallOrmBundle\QueryBuilder;

use Sebk\SmallOrmBundle\Dao\AbstractDao;

/**
 * Sql query builder
 */
class QueryBuilder
{
    public $from;

    /**
     * Construct QueryBuilder
     * @param AbstractDao $baseDao
     * @param string $baseAlias
     */
    public function __construct(AbstractDao $baseDao, $baseAlias = null)
    {
        if($baseAlias == null) {
            $baseAlias = $baseDao->getModelName();
        }

        $this->from = new FromBuilder($baseDao, $baseAlias);
    }

    /**
     * Format select part as string
     * @return string
     */
    public function getFieldsForSqlAsString()
    {
        $resultArray = $this->from->getFieldsForSqlAsArray();

        return implode(", ", $resultArray);
    }

    /**
     * Format from part as string
     * @return string
     */
    public function getFromForSqlAsString()
    {
        $result = $this->from->getFromForSql();

        return $result;
    }

    /**
     * Return sql statement for this query
     * @return string
     */
    public function getSql() {
        $sql = "SELECT ";
        $sql .= $this->getFieldsForSqlAsString();
        $sql .= " FROM ";
        $sql .= $this->getFromForSqlAsString();

        return $sql;
    }
}