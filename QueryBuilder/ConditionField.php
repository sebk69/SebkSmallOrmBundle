<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\QueryBuilder;

use Sebk\SmallOrmBundle\QueryBuilder\FromBuilder;

/**
 * Field definition for condition
 */
class ConditionField
{
    public $model;
    public $fieldNameInModel;

    /**
     * Construct field definition
     * @param FromBuilder $model
     * @param string $fieldNameInModel
     */
    public function __construct(FromBuilder $model, $fieldNameInModel)
    {
        $this->model            = $model;
        $this->fieldNameInModel = $fieldNameInModel;
    }

    public function getSql()
    {
        return $this->model->getAlias().".".$this->model->getDao()->getField($this->fieldNameInModel)->getDbName();
    }
}