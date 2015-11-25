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
    protected $from;
    protected $joins             = array();
    protected $where;
    protected $forcedSql;
    protected $parameters        = array();
    protected $offset            = null;
    protected $limit             = null;
    protected $orderBy           = array();
    protected $groupBy;
    protected $groupByOperations = array();
    protected $rawSelect         = null;

    /**
     * Construct QueryBuilder
     * @param AbstractDao $baseDao
     * @param string $baseAlias
     */
    public function __construct(AbstractDao $baseDao, $baseAlias = null)
    {
        if ($baseAlias == null) {
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
        /*if ($this->groupBy === null) {
            $exludeJoinsArray = array();
        } else {
            $exludeJoinsArray = $this->getJoinsWithoutGroupBy();
        }*/

        $resultArray = $this->from->getFieldsForSqlAsArray();
        foreach ($this->joins as $join) {
            //if (!in_array($join->getAlias(), $exludeJoinsArray)) {
                $resultArray = array_merge($resultArray,
                    $join->getFieldsForSqlAsArray());
            //}
        }

        foreach ($this->groupByOperations as $operation) {
            $resultArray[] = $operation->getSql();
        }

        return implode(", ", $resultArray);
    }

    /**
     * @param array $joinsArray
     * @return array
     */
    protected function getJoinsWithoutGroupBy($joinsArray = null)
    {
        if ($joinsArray === null) {
            $joinsArray = array($this->groupBy);
        }

        $startJoinArray = $joinsArray;
        foreach ($this->joins as $join) {
            if (in_array($join->getFromAlias(), $joinsArray) && !in_array($join->getAlias(),
                    $joinsArray)) {
                $joinsArray[] = $join->getAlias();
            }
        }

        if (count($joinsArray) != count($startJoinArray)) {
            $joinsArray = $this->getJoinsWithoutGroupBy($joinsArray);
        } else {
            unset($joinsArray[0]);
        }

        return $joinsArray;
    }

    /**
     * This method will replace object and return an array per record corresponding to sql
     * @param type $sql
     * @return \Sebk\SmallOrmBundle\QueryBuilder\QueryBuilder
     */
    public function rawSelect($sql)
    {
        $this->rawSelect = $sql;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isRawSelect()
    {
        return $this->rawSelect !== null;
    }

    /**
     * @param string $alias
     * @return \Sebk\SmallOrmBundle\QueryBuilder\QueryBuilder
     * @throws QueryBuilderException
     */
    public function groupBy($alias)
    {
        if ($alias == $this->from->getAlias()) {
            $this->groupBy = $alias;

            return $this;
        }

        foreach ($this->joins as $join) {
            if ($alias == $join->getAlias()) {
                $this->groupBy = $alias;

                return $this;
            }
        }

        throw new QueryBuilderException("Group by alias not fond in joins ($alias)");
    }

    /**
     * @return string
     */
    public function getGroupByAlias()
    {
        return $this->groupBy;
    }

    /**
     * @return array
     */
    public function getGroupByOperations()
    {
        return $this->groupByOperations;
    }

    /**
     * @param string $alias
     * @param string $field
     * @param string $operation
     * @return \Sebk\SmallOrmBundle\QueryBuilder\QueryBuilder
     */
    public function addGroupByOperation($operation, $modelAlias, $field,
                                        $operationAlias)
    {
        $this->groupByOperations[] = new groupByOperation($operation,
            $modelAlias, $field, $operationAlias);

        return $this;
    }

    /**
     * Get relation identified by alias
     * If null => return from base relation
     * @param string $alias
     * @return FromBuilder
     * @throws QueryBuilderException
     */
    public function getRelation($alias = null)
    {
        if ($alias === null || $alias == $this->from->getAlias()) {
            return $this->from;
        }

        foreach ($this->joins as $joinAlias => $join) {
            if ($alias == $joinAlias) {
                return $join;
            }
        }

        throw new QueryBuilderException("Can't find relation '$alias'");
    }

    /**
     *
     * @return array
     */
    public function getChildRelationsForAlias($alias)
    {
        $result = array();
        foreach ($this->joins as $join) {
            if ($join->getFromAlias() == $alias /*&& !in_array($join->getAlias(),
                    $this->getJoinsWithoutGroupBy())*/) {
                $result[] = $join;
            }
        }
        return $result;
    }

    /**
     * Format from part as string
     * @return string
     */
    public function getFromForSqlAsString()
    {
        $result = $this->from->getSql();

        return $result;
    }

    /**
     * Add join
     * @param string $fromAlias
     * @param string $relationAlias
     * @param string $alias
     * @return \Sebk\SmallOrmBundle\QueryBuilder\JoinBuilder
     */
    public function join($fromAlias, $relationAlias, $alias = null,
                         $type = "join")
    {
        if ($alias == null) {
            $alias = $relationAlias;
        }
        switch ($type) {
            case "join":
                $join = new JoinBuilder(null, $alias);
                break;
            case "left join":
                $join = new LeftJoinBuilder(null, $alias);
                break;
            case "inner join":
                $join = new InnerJoinBuilder(null, $alias);
                break;
            case "full outer join":
                $join = new FullOuterJoinBuilder(null, $alias);
                break;

            default:
                new QueryBuilderException("Join type '$type' now exists");
        }

        $join->setParent($this);
        $join->setFrom($this->getRelation($fromAlias), $relationAlias);
        $this->joins[$alias] = $join;
        $this->joins[$alias]->buildBaseConditions();

        return $join;
    }

    /**
     * Add left join
     * @param string $fromAlias
     * @param string $relationAlias
     * @param string $alias
     * @return \Sebk\SmallOrmBundle\QueryBuilder\LeftJoinBuilder
     */
    public function leftJoin($fromAlias, $relationAlias, $alias = null)
    {
        return $this->join($fromAlias, $relationAlias, $alias, "left join");
    }

    /**
     * Add inner join
     * @param string $fromAlias
     * @param string $relationAlias
     * @param string $alias
     * @return \Sebk\SmallOrmBundle\QueryBuilder\InnerJoinBuilder
     */
    public function innerJoin($fromAlias, $relationAlias, $alias = null)
    {
        return $this->join($fromAlias, $relationAlias, $alias, "inner join");
    }

    /**
     * Add full outer join
     * @param string $fromAlias
     * @param string $relationAlias
     * @param string $alias
     * @return \Sebk\SmallOrmBundle\QueryBuilder\InnerJoinBuilder
     */
    public function fullOuterJoin($fromAlias, $relationAlias, $alias = null)
    {
        return $this->join($fromAlias, $relationAlias, $alias, "full outer join");
    }

    /**
     * Initialize where clause
     * @return Bracket
     */
    public function where()
    {
        $this->where = new Bracket($this);

        return $this->where;
    }

    /**
     * Return sql statement for this query
     * @return string
     */
    public function getSql()
    {
        if ($this->forcedSql !== null) {
            return $this->forcedSql;
        }

        $sql = "SELECT ";
        if($this->isRawSelect()) {
            $sql .= $this->rawSelect;
        } else {
            $sql .= $this->getFieldsForSqlAsString();
        }
        $sql .= " FROM ";
        $sql .= $this->getFromForSqlAsString();

        foreach ($this->joins as $join) {
            $sql .= $join->getSql();
        }

        if ($this->where !== null && trim($this->where->getSql())) {
            $sql .= " WHERE ";
            $sql .= $this->where->getSql();
        }

        if ($this->groupBy !== null) {
            $groupBy = array();
            if ($this->groupBy == $this->from->getAlias()) {
                foreach ($this->from->getDao()->getPrimaryKeys() as $key) {
                    $groupBy[] = $this->from->getAlias().".".$key->getDbName();
                }
            } else {
                foreach ($this->joins[$this->groupBy]->getDao()->getPrimaryKeys() as $key) {
                    $groupBy[] = $this->joins[$this->groupBy]->getAlias().".".$key->getDbName();
                }
            }


            $sql .= " GROUP BY ".implode(", ", $groupBy);
        }

        if (count($this->orderBy)) {
            $sql .= " ORDER BY ";
            $orderBy = array();
            foreach ($this->orderBy as $orderByField) {
                $orderBy[] = $orderByField->getSql();
            }
            $sql .= implode(", ", $orderBy);
        }

        if ($this->offset !== null) {
            $sql .= " LIMIT ".$this->offset.", ".$this->limit;
        }

        return $sql;
    }

    /**
     * Limit result
     * @param string $offset
     * @param string $limit
     */
    public function limit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit  = $limit;

        return $this;
    }

    public function paginate($page, $pageSize)
    {
        $this->offset = ($page - 1) * $pageSize;
        $this->limit  = $pageSize;

        return $this;
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
    public function forceSql($sql)
    {
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
    public function getFieldForCondition($fieldName, $modelAlias = null)
    {
        if ($this->from->getAlias() == $modelAlias || $modelAlias === null) {
            if ($this->from->getDao()->hasField($fieldName)) {
                return new ConditionField($this->from, $fieldName);
            }
        }

        foreach ($this->joins as $joinAlias => $join) {
            if ($joinAlias == $modelAlias) {
                if ($join->getDao()->hasField($fieldName)) {
                    return new ConditionField($join, $fieldName);
                }
            }
        }

        throw new QueryBuilderException("Field '$fieldName' is not in model aliased '$modelAlias'");
    }

    /**
     * Get orderby field object
     * @param string $fieldName
     * @param string $modelAlias
     * @return \Sebk\SmallOrmBundle\QueryBuilder\ConditionField
     * @throws QueryBuilderException
     */
    public function addOrderBy($fieldName, $modelAlias = null, $sens = "ASC")
    {
        if ($this->from->getAlias() == $modelAlias || $modelAlias === null) {
            if ($this->from->getDao()->hasField($fieldName)) {
                $this->orderBy[] = new OrderByField($this->from, $fieldName,
                    $sens);
                return $this;
            }
        }

        foreach ($this->joins as $joinAlias => $join) {
            if ($joinAlias == $modelAlias) {
                if ($join->getDao()->hasField($fieldName)) {
                    $this->orderBy[] = new OrderByField($join, $fieldName, $sens);
                    return $this;
                }
            }
        }

        throw new QueryBuilderException("Field '$fieldName' is not in model aliased '$modelAlias'");
    }

    /**
     * Return where to be completed
     * @return Bracket
     */
    public function getWhere()
    {
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
    /**
     * Get raw result of query
     * @return array
     *
      public function getRawResult()
      {
      return $this->from->getDao()->getRawResult($this);
      }

      public function getResult()
      {
      return $this->from->getDao()->populate($this, $this->getRawResult());
      }
     *
     */
}