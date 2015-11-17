<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigtht 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\QueryBuilder;

use Sebk\SmallOrmBundle\Dao\AbstractDao;

class UpdateBuilder
{
    protected $from;
    protected $where;
    protected $fieldsUpdate = array();
    protected $parameters = array();

    /**
     * Construct UpdateBuilder
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
     * Initialize where clause
     * @return Bracket
     */
    public function where()
    {
        $this->where = new Bracket($this);

        return $this->where;
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
     * Add field update
     * @param string $field
     * @param mixed $update
     * @return \Sebk\SmallOrmBundle\QueryBuilder\UpdateBuilder
     */
    public function addFieldToUpdate($field, $update)
    {
        $this->fieldsUpdate[] = new FieldUpdate($this->from->getDao()->getField($field), $update);

        return $this;
    }

    /**
     * Get sql for update
     * @return string
     */
    public function getSql()
    {
        $sql = "UPDATE ".$this->from->getSql()." ";

        $updates = array();
        foreach($this->fieldsUpdate as $fieldUpdate) {
            $updates[] = $fieldUpdate->getSql();
        }

        $sql .= "SET ".implode(", ", $updates);

        if($this->where !== null) {
            $sql .= " WHERE ".$this->where->getSql();
        }

        return $sql;
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