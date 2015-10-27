<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Factory;

use \Sebk\SmallOrmBundle\Dao\Model;

/**
 *
 */
class Validator
{
    protected $daoFactory;
    protected $config;

    /**
     * Construct dao factory
     * @param \Sebk\SmallOrmBundle\Factory\Connections $connectionFactory
     * @param type $config
     */
    public function __construct($daoFactory, $config)
    {
        $this->daoFactory = $daoFactory;
        $this->config     = $config;
    }

    /**
     * Get validator of a model
     * @param type $bundle
     * @param type $model
     * @return type
     * @throws ConfigurationException
     * @throws DaoNotFoundException
     */
    public function get(Model $model)
    {
        if (!isset($this->config[$model->getBundle()])) {
            throw new ConfigurationException("Bundle '$bundle' is not configured");
        }

        foreach ($this->config[$bundle]["connections"] as $connectionName => $connectionsParams) {
            $className = $connectionsParams["validator_namespace"].'\\'.$model->getModelName();
            if (class_exists($className)) {
                return new $className($this->daoFactory, $model);
            }
        }

        throw new DaoNotFoundException("Validator of model $model of bundle $bundle not found");
    }
}