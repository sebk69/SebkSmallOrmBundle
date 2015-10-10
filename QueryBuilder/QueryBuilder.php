<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */
namespace Sebk\SmallOrmBundle\QueryBuilder;

use Sebk\SmallOrmBundle\Dao\AbstractDao;

/**
 * Sql query builder
 */
class QueryBuilder
{
    public $from;
    public $where;
    public $forcedSql;
    public $parameters = array();

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
     * Initialize where clause
     * @return Bracket
     */
    public function where() {
        $this->where = new Bracket($this);

        return $this->where;
    }

    /**
     * Return sql statement for this query
     * @return string
     */
    public function getSql() {
        if($this->forcedSql !== null) {
            return $this->forcedSql;
        }

        $sql = "SELECT ";
        $sql .= $this->getFieldsForSqlAsString();
        $sql .= " FROM ";
        $sql .= $this->getFromForSqlAsString();
        if($this->where !== null) {
            $sql .= " WHERE ";
            $sql .= $this->where->getSql();
        }

        return $sql;
    }

    /**
     * Is sql has been forced
     * @return boolean
     */
    public function isSqlHasBeenForced()
    {
        return $this->forcedSql === null;
    }

    /**
     * Force sql to execute
     * @param string $sql
     */
    public function forceSql($sql) {
        $this->forcedSql = $sql;

        return $this;
    }

    /**
     * Get condition field object
     * @param string $fieldName
     * @param string $modelAlias
     * @return \Sebk\SmallOrmBundle\QueryBuilder\ConditionField
     * @throws QueryBuilderException
     */
    public function getFieldForCondition($fieldName, $modelAlias) {
        if($this->from->getAlias() == $modelAlias) {
            if($this->from->getDao()->hasField($fieldName)) {
                return new ConditionField($this->from, $fieldName);
            }
        }

        throw new QueryBuilderException("Field '$fieldName' is not in model aliased '$modelAlias'");
    }

    /**
     * Return where to be completed
     * @return Bracket
     */
    public function getWhere() {
        return $this->where;
    }

    /**
     * Set parameter
     * @param string $paramName
     * @param string $value
     * @return \Sebk\SmallOrmBundle\QueryBuilder\QueryBuilder
     */
    public function setParameter($paramName, $value)
    {
        $this->parameters[$paramName] = $value;

        return $this;
    }

    /**
     * Get query parameters
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}