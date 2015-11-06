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
class groupByOperation
{
    protected $field;
    protected $modelAlias;
    protected $operation;
    protected $operationAlias;

    /**
     *
     * @param string $operation
     * @param string $modelAlias
     * @param string $field
     * @param string $operationAlias
     * @throws QueryBuilderException
     */
    public function __construct($operation, $modelAlias, $field, $operationAlias)
    {
        switch (strtolower($operation)) {
            case "avg":
            case "count":
            case "countDistinct":
            case "groupConcat":
            case "max":
            case "min":
            case "stdDev":
            case "stdDevPop":
            case "stdDevSamp":
            case "sum":
            case "varPop":
            case "varSamp":
                $this->field     = $field;
                $this->modelAlias     = $modelAlias;
                $this->operation = $operation;
                $this->operationAlias = $operationAlias;
                break;

            default:
                throw new QueryBuilderException("Unknown group by operation ($operation)");
        }
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->operationAlias;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        switch (strtolower($this->operation)) {
            case "avg":
                return "AVG(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "count":
                return "COUNT(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "countDistinct":
                return "COUNT(DISTINCT ".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "groupConcat":
                return "GROUP_CONCAT(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "max":
                return "MAX(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "min":
                return "MIN(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "stdDev":
                return "STDDEV(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "stdDevPop":
                return "STDDEV_POP(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "stdDevSamp":
                return "STDDEV_SAMP(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "sum":
                return "SUM(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "varPop":
                return "VAR_POP(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
            case "varSamp":
                return "VAR_SAMP(".$this->modelAlias.".".$this->field.") AS ".$this->operationAlias;
                break;
        }
    }
}