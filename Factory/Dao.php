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
    protected $container;
    protected static $loadedDao = array();

    /**
     * Construct dao factory
     * @param \Sebk\SmallOrmBundle\Factory\Connections $connectionFactory
     * @param type $config
     */
    public function __construct(Connections $connectionFactory, $config, $container)
    {
        $this->connectionFactory = $connectionFactory;
        $this->config            = $config;
        $this->container         = $container;
    }

    /**
     * Reset Factory elements
     *
     * @return $this
     */
    public function reset()
    {
        static::$loadedDao = [];

        return $this;
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
        if (!isset($this->config[$bundle])) {
            throw new ConfigurationException("Bundle '$bundle' is not configured");
        }

        if (isset(static::$loadedDao[$bundle][$model])) {
            return static::$loadedDao[$bundle][$model];
        }

        foreach ($this->config[$bundle]["connections"] as $connectionName => $connectionsParams) {
            $className = $connectionsParams["dao_namespace"].'\\'.$model;
            if (class_exists($className)) {
                static::$loadedDao[$bundle][$model] = new $className($this->connectionFactory->get($connectionName),
                    $this, $connectionsParams["model_namespace"], $model,
                    $bundle,
                    $this->container);

                return static::$loadedDao[$bundle][$model];
            }
        }

        throw new DaoNotFoundException("Dao of model $model of bundle $bundle not found");
    }

    /**
     * Return the class name with namespace
     * @param $connectionNameOfDao
     * @param $bundle
     * @param $model
     * @return string
     * @throws ConfigurationException
     */
    public function getDaoFullClassName($connectionNameOfDao, $bundle, $model)
    {
        // get namespace
        $namespace = $this->getDaoNamespace($connectionNameOfDao, $bundle);

        // get full class name
        $className = $namespace . '\\' . $model;

        return $className;
    }

    /**
     * Get namespace for a connection and bundle
     * @param $connectionNameOfDao
     * @param $bundle
     * @return mixed
     * @throws ConfigurationException
     */
    public function getDaoNamespace($connectionNameOfDao, $bundle)
    {
        // bundle exists in configuration
        if (!isset($this->config[$bundle])) {
            throw new ConfigurationException("Bundle '$bundle' is not configured");
        }

        // check connection exists in bundle
        if (!isset($this->config[$bundle]["connections"][$connectionNameOfDao])) {
            throw new ConfigurationException("Connection '".$connectionNameOfDao."' is not found for bundle '".$bundle."'");
        }

        return $this->config[$bundle]["connections"][$connectionNameOfDao]["dao_namespace"];
    }

    /**
     * Return the class name of model with namespace
     * @param $connectionNameOfDao
     * @param $bundle
     * @param $model
     * @return string
     */
    public function getModelFullClassName($connectionNameOfDao, $bundle, $model)
    {
        // get namespace
        $namespace = $this->getModelNamespace($connectionNameOfDao, $bundle);

        // get full class name
        $className = $namespace . '\\' . $model;

        return $className;
    }

    /**
     * Get namespace of model for a connection and bundle
     * @param $connectionNameOfDao
     * @param $bundle
     * @return mixed
     * @throws ConfigurationException
     */
    public function getModelNamespace($connectionNameOfDao, $bundle)
    {
        // bundle exists in configuration
        if(!isset($this->config[$bundle])) {
            throw new ConfigurationException("Bundle '$bundle' is not configured");
        }

        // check connection exists in bundle
        if (!isset($this->config[$bundle]["connections"][$connectionNameOfDao])) {
            throw new ConfigurationException("Connection '".$connectionNameOfDao."' is not found for bundle '".$bundle."'");
        }

        return $this->config[$bundle]["connections"][$connectionNameOfDao]["model_namespace"];
    }

    /**
     * Get file where is defined the dao
     * @param $bundle
     * @param $model
     * @param bool $evenIfNotFound
     * @return string
     * @throws ConfigurationException
     * @throws DaoNotFoundException
     */
    public function getFile($connectionNameOfDao, $bundle, $model, $evenIfNotFound = false)
    {
        // get class name
        $className = $this->getDaoFullClassName($connectionNameOfDao, $bundle, $model);

        // return file
        return $this->getFileForClass($bundle, $className, $evenIfNotFound);
    }

    /**
     * Get file where is defined the model
     * @param $connectionNameOfDao
     * @param $bundle
     * @param $model
     * @param bool $evenIfNotFound
     * @return string
     */
    public function getModelFile($connectionNameOfDao, $bundle, $model, $evenIfNotFound = false)
    {
        // get class name
        $className = $this->getModelFullClassName($connectionNameOfDao, $bundle, $model);

        // return file
        return $this->getFileForClass($bundle, $className, $evenIfNotFound);
    }

    /**
     * Get the file for a class
     * @param $bundle
     * @param $fullClassName
     * @param bool $evenIfNotFound
     * @return string
     * @throws DaoNotFoundException
     */
    private function getFileForClass($bundle, $fullClassName, $evenIfNotFound = false)
    {
        // Use reflector if class exists
        if (class_exists($fullClassName)) {
            $reflector = new \ReflectionClass($fullClassName);

            // return file name
            return $reflector->getFileName();
        } elseif(!$evenIfNotFound) {
            throw new DaoNotFoundException("Class not found : $fullClassName");
        }

        // get namespace parts
        $nameSpaceParts = explode("\\", $fullClassName);

        // create relative path to file
        if($nameSpaceParts[0] != "App") {
            unset($nameSpaceParts[0]);
            unset($nameSpaceParts[1]);
        } else {
            unset($nameSpaceParts[0]);
            unset($nameSpaceParts[1]);
            unset($nameSpaceParts[2]);
        }
        $relativePath = "";
        foreach($nameSpaceParts as $nameSpacePart) {
            $relativePath .= $nameSpacePart."/";
        }
        $relativePath = substr($relativePath, 0, strlen($relativePath) - 1).".php";

        // create file path
        return $this->container->get('kernel')->locateResource("@".$bundle)."/".$relativePath;
    }
}