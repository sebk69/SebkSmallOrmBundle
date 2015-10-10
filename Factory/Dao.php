<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */
namespace Sebk\SmallOrmBundle\Factory;

/**
 *
 */
class Dao
{
    protected $connectionFactory;
    protected $config;
    protected static $loadedDao = array();

    /**
     * Construct dao factory
     * @param \Sebk\SmallOrmBundle\Factory\Connections $connectionFactory
     * @param type $config
     */
    public function __construct(Connections $connectionFactory, $config)
    {
        $this->connectionFactory = $connectionFactory;
        $this->config = $config;
    }

    /**
     * Get dao of a model
     * @param type $bundle
     * @param type $model
     * @return type
     * @throws ConfigurationException
     * @throws DaoNotFoundException
     */
    public function get($bundle, $model)
    {
        if(!isset($this->config[$bundle])) {
            throw new ConfigurationException("Bundle '$bundle' is not configured");
        }

        if(isset(static::$loadedDao[$bundle][$model])) {
            return static::$loadedDao[$bundle][$model];
        }

        foreach($this->config[$bundle]["connections"] as $connectionName => $connectionsParams) {
            $className = $connectionsParams["dao_namespace"].'\\'.$model;
            if(class_exists($className)) {
                static::$loadedDao[$bundle][$model] = new $className($this->connectionFactory->get($connectionName), $connectionsParams["model_namespace"]);
                return static::$loadedDao[$bundle][$model];
            }
        }

        throw new DaoNotFoundException("Dao of model $model of bundle $bundle not found");
    }
}