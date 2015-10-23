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
class JoinBuilder extends FromBuilder
{
    protected $from;
    protected $relation;
    protected $bracket;
    protected $parent;
    
    /**
     * @param \Sebk\SmallOrmBundle\QueryBuilder\QueryBuilder $parent
     * @return \Sebk\SmallOrmBundle\QueryBuilder\JoinBuilder
     */
    public function setParent(QueryBuilder $parent)
    {
        $this->parent = $parent;
        
        return $this;
    }
    
    /**
     * Set from and build primary conditions of relation
     * @param \Sebk\SmallOrmBundle\QueryBuilder\FromBuilder $from
     * @param string $relationAlias
     * @return \Sebk\SmallOrmBundle\QueryBuilder\JoinBuilder
     * @throws JoinBuilderException
     */
    public function setFrom(FromBuilder $from, $relationAlias)
    {
        if (!$this->parent instanceof QueryBuilder) {
            throw new JoinBuilderException("Parent must be defined before set from");
        }

        $this->from     = $from;
        $this->relation = $this->from->getDao()->getRelation($relationAlias);
        $this->dao = $this->relation->getDao();

        return $this;
    }

    /**
     *
     * @return Relation
     */
    public function getDaoRelation()
    {
        return $this->relation;
    }

    /**
     * Build primary keys relation conditions
     * @throws JoinBuilderException
     */
    public function buildBaseConditions()
    {
        if($this->bracket !== null) {
            throw new JoinBuilderException("Base condition already defined");
        }
        
        $this->bracket = new Bracket($this);
        $first         = true;
        foreach ($this->relation->getKeys() as $fromField => $toField) {
            if ($first) {
                $this->bracket->firstCondition(
                    $this->parent->getFieldForCondition($fromField,
                        $this->from->getAlias()), "=",
                    $this->parent->getFieldForCondition($toField,
                        $this->getAlias()));
            } else {
                $this->bracket->andCondition(
                    $this->parent->getFieldForCondition($fromField,
                        $this->from->getAlias()), "=",
                    $this->parent->getFieldForCondition($toField,
                        $this->getAlias()));
            }

            $first = false;
        }

        return $this;
    }

    /**
     * Add conditions to relation
     * @return Bracket
     * @throws JoinBuilderException
     */
    public function joinCondition()
    {
        if (!isset($this->bracket)) {
            throw new JoinBuilderException("Join condition without setFrom");
        }

        return $this->bracket;
    }

    /**
     * End join
     * @return QueryBuilder
     */
    public function endJoin()
    {
        return $this->parent;
    }

    /**
     * Get sql code for join
     * @return string
     */
    public function getSql($type = "JOIN") {
        $sql = " ".$type." ";

        $sql .= parent::getSql();

        $sql .= " ON ";

        $sql .= $this->bracket->getSql();

        return $sql;
    }

    public function getFromAlias() {
        return $this->from->getAlias();
    }
}