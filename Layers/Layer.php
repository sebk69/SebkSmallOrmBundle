<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015-2017 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Layers;


use Sebk\SmallOrmBundle\Factory\Connections;
use Symfony\Component\Yaml\Yaml;

class Layer
{
    // Allowed parameters
    const CONNECTION_PARAMETER = "connection";
    const DEPENDS_PARAMETER = "depends";

    // Properties
    protected $layerRootPath;
    protected $layerName;
    protected $connectionsFactory;
    protected $configFilePath;
    protected $connection;
    protected $dependencies = [];

    /**
     * Layer constructor.
     * @param $layerRootPath
     * @param $layerName
     */
    public function __construct($layerRootPath, $layerName, Connections $connectionsFactory)
    {
        $this->layerRootPath = $layerRootPath;
        $this->layerName = $layerName;
        $this->connectionsFactory = $connectionsFactory;
        $this->configFilePath = $this->layerRootPath."/".$this->layerName."/config.yml";
        $this->loadLayer();
    }

    /**
     * Get layer name
     * @return mixed
     */
    public function getName()
    {
        return $this->layerName;
    }

    /**
     * Get dependencies
     * @return mixed
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Get connection
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Load layer :
     * - config
     * - scripts
     * TODO - fixtures
     */
    protected function loadLayer()
    {
        // Load the config
        $this->loadConfig();
    }

    /**
     * Load config in class
     * @throws LayerConfigNotFoundException
     */
    protected function loadConfig()
    {
        // Check config file
        if(!file_exists($this->configFilePath)) {
            throw new LayerConfigNotFoundException("The config file of layer ".$this->layerName." can't be found. It should be in '".$this->configFilePath."'");
        }

        // Parse config file
        $config = Yaml::parse(file_get_contents($this->configFilePath));

        // Check syntax of config
        $this->checkConfigSyntax($config);

        // Load values
        $this->connection = $this->connectionsFactory->get($config[static::CONNECTION_PARAMETER]);
        if(isset($config[static::DEPENDS_PARAMETER])) {
            $rawDependencies = $config[static::DEPENDS_PARAMETER];
            foreach ($rawDependencies as $rawDependance) {
                if(strstr($rawDependance, "@")) {
                    // not in same bundle
                    $exploded = explode("@", $rawDependance);
                    $this->dependencies[] = ["bundle" => $exploded[1], "layer" => $exploded[0]];
                } else {
                    // same bundle
                    $this->dependencies[] = ["layer" => $rawDependance];
                }
            }
        }
    }

    /**
     * Check config syntax
     * @param $config
     * @throws LayerConnectionNotFoundException
     * @throws LayerSyntaxError
     * @throws LayerUnknownParameter
     */
    protected function checkConfigSyntax($config)
    {
        // initialize common errors messages
        $layerPath = " in '".$this->configFilePath."'";

        // Initialize require parameters
        $connectionFound = false;

        // Foreach config parameters
        foreach($config as $configParameter => $configValue) {
            switch($configParameter) {
                // Connection found
                case static::CONNECTION_PARAMETER:
                    $connectionFound = true;
                    break;

                // Check depends parameter
                case static::DEPENDS_PARAMETER:
                    if(!is_array($configValue)) {
                        throw new LayerSyntaxError("The value for 'depends' parameter must be an array$layerPath");
                    }
                    break;

                // Unknown parameter
                default:
                    throw new LayerUnknownParameter("Parameter '".$configParameter."' is not valid$layerPath");
            }
        }

        // Check require parameters
        if(!$connectionFound) {
            throw new LayerConnectionNotFoundException("You must configure connection$layerPath");
        }
    }

    /**
     * Execute scripts
     * @return bool
     */
    public function executeScripts()
    {
        // Get scripts directory path
        $scriptsPath = $this->layerRootPath."/".$this->getName()."/scripts";
        
        // scan script directory
        $scriptsDir = scandir($scriptsPath);
        foreach($scriptsDir as $scriptFilename) {
            if(substr($scriptFilename, 0, 1) != ".") {
                // if file is not hidden, execute it
                $sql = file_get_contents($scriptsPath . "/" . $scriptFilename);
                echo "Execute script : ".$scriptFilename."\n";
                $this->connection->execute($sql);
            }
        }

        return true;
    }
}