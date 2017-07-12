<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015-2017 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Generator;


use Sebk\SmallOrmBundle\Dao\DaoException;
use Sebk\SmallOrmBundle\Database\Connection;
use Sebk\SmallOrmBundle\Factory\Connections;
use Sebk\SmallOrmBundle\Factory\Dao;
use Sebk\SmallOrmBundle\Factory\DaoNotFoundException;

class DaoGenerator
{
    protected $connectionName;
    protected $bundle;
    protected $daoFactory;
    protected $connections;
    protected $dbGateway;
    protected $container;

    protected static $daoTemplate = "<?php
namespace [nameSpace];

use Sebk\\SmallOrmBundle\\Dao\\AbstractDao;

class [daoName] extends AbstractDao
{
    protected function build()
    {
    }
}";

    protected static $modelTemplate = "<?php
namespace [namespace];

use Sebk\SmallOrmBundle\Dao\Model;

class [modelName] extends Model
{
}";

    /**
     * DaoGenerator constructor.
     * @param Dao $daoFactory
     * @param Connections $connections
     */
    public function __construct(Dao $daoFactory, Connections $connections, $container)
    {
        $this->daoFactory = $daoFactory;
        $this->connections = $connections;
        $this->container = $container;
    }

    /**
     * Set class parameters
     * @param $connectionName
     * @param $bundle
     * @return $this
     */
    public function setParameters($connectionName, string $bundle)
    {
        $this->connectionName = $connectionName;
        $this->dbGateway = new DbGateway($this->connections->get($connectionName));
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Get standard dao class name
     * @return string
     */
    private function getDaoClassName($table) {
        return $this->camelize($table);
    }

    /**
     * Get filepath to DAO
     * @return string
     */
    private function getDaoFilePath($dbTableName)
    {
        return $this->daoFactory->getFile($this->connectionName, $this->bundle, $this->getDaoClassName($dbTableName), true);
    }

    /**
     * Get content of dao file
     * @return string
     */
    public function getDaoFileContent($dbTableName)
    {
        $filePath = $this->getDaoFilePath($dbTableName);
        if(file_exists($filePath)) {
            return file_get_contents($filePath);
        } else {
            $template = str_replace("[nameSpace]", $this->daoFactory->getDaoNamespace($this->connectionName, $this->bundle), static::$daoTemplate);
            $template = str_replace("[daoName]", $this->getDaoClassName($dbTableName), $template);

            return $template;
        }
    }

    /**
     * Put content in dao file
     * @param $content
     * @return $this
     */
    public function putDaoFileContent($dbTableName, $content)
    {
        $filePath = $this->getDaoFilePath($dbTableName);
        file_put_contents($filePath, $content);

        return $this;
    }

    /**
     * Camelize string
     * @param $string
     * @param bool $firstLetterLowercase
     * @return string
     */
    private static function camelize($string, $firstLetterLowercase = false, $pluralize = false)
    {
        $parts = explode("_", $string);

        $result = "";
        foreach ($parts as $part) {
            if($pluralize) {
                $result .= ucfirst(static::pluralize($part));
            } else {
                $result .= ucfirst($part);
            }
        }

        if($firstLetterLowercase) {
            $result = lcfirst($result);
        }

        return $result;
    }

    /**
     * Return plural of a string
     * @param $singular
     * @return string
     */
    private static function pluralize($singular) {
        $last_letter = strtolower($singular[strlen($singular)-1]);
        switch($last_letter) {
            case 'y':
                return substr($singular,0,-1).'ies';
            case 's':
                return $singular.'es';
            default:
                return $singular.'s';
        }
    }

    /**
     * Generate build function
     * @return string
     */
    protected function generateBuildFunction($dbTableName)
    {
        // retreive database description
        $connection = $this->connections->get($this->connectionName);
        $description = $this->dbGateway->getDescription($dbTableName);

        // get generator configs
        $configCollection = new ConfigCollection($this->connectionName, $this->container);
        $configCollection->loadConfigs();

        // build function
        $output = 'protected function build()
    {
        $this->setDbTableName("'.$dbTableName.'")
            ->setModelName("'.$this->getDaoClassName($dbTableName).'")
';

        // Fields
        foreach ($description as $record) {
            switch($record["Default"]) {
                case "now()":
                case "CURRENT_TIMESTAMP":
                    $default = 'date("Y-m-d H:i:s")';
                    break;

                case "NULL":
                case "null":
                case "":
                    $default = null;
                    break;

                default:
                    $default = '"'.$record["Default"].'"';
            }

            if($record["Key"] != "PRI") {
                $output .= '            ->addField("' . $record["Field"] . '", "' . static::camelize($record["Field"], true) . '"' . ($default != null ? ', '.$default : '').')
';
            } else {
                $output .= '            ->addPrimaryKey("' . $record["Field"] . '", "' . static::camelize($record["Field"], true) . '")
';
            }
        }

        // To one relations
        foreach ($this->dbGateway->getToOnes($dbTableName) as $toOne) {
            try {
                $toBundle = $configCollection->getTableBundle($toOne["toTable"]);
                $output .= '            ->addToOne("' . static::camelize($toOne["relation"], true) .
                    '", ["' . static::camelize($toOne["fromField"], true) . '" => "' . static::camelize($toOne["toField"], true) . '"], "' . $this->getDaoClassName($toOne["toTable"]) . '"';
                if ($toBundle == $this->bundle) {
                    $output .= ')
';
                } else {
                    $output .= ', "' . $toBundle . '")
';
                }
            } catch(TableNotFoundException $e) {

            }
        }

        // To many relations
        foreach ($this->dbGateway->getToManys($dbTableName) as $toMany) {
            try {
                $toBundle = $configCollection->getTableBundle($toMany["toTable"]);
                $output .= '            ->addToMany("'.static::camelize($toMany["toTable"], true, true).
                    '", ["'.static::camelize($toMany["toField"], true).'" => "'.static::camelize($toMany["fromField"], true).'"], "'.$this->getDaoClassName($toMany["toTable"]).'"';
                if($toBundle == $this->bundle) {
                    $output .= ');
';
                } else {
                    $output .= ', "'.$toBundle.'");
';
                }
            } catch(TableNotFoundException $e) {

            }
        }

        $output .= '        ;
    }';

        return $output;
    }

    /**
     * recompute files for a table
     */
    public function recomputeFilesForTable($dbTableName)
    {
        // check if table is configured in other bundle
        $configCollection = new ConfigCollection($this->connectionName, $this->container);
        $configCollection->loadConfigs();
        try {
            $tableBundle = $configCollection->getTableBundle($dbTableName);
            if($tableBundle != $this->bundle) {
                throw new \Exception("The table ".$dbTableName." already configured for bundle ".$tableBundle);
            }
        } catch (TableNotFoundException $e) {

        }

        // Build function for the DAO
        $content = $this->getDaoFileContent($dbTableName);
        $parser = new FileParser($content);
        $buildPos = $parser->findFunctionPos("build");
        $content = substr_replace($content, $this->generateBuildFunction($dbTableName), $buildPos["start"], $buildPos["end"] - $buildPos["start"]);
        $this->putDaoFileContent($dbTableName, $content);

        // Create model class if not exists
        $modelFile = $this->daoFactory->getModelFile($this->connectionName, $this->bundle, static::camelize($dbTableName), true);
        if(!file_exists($modelFile)) {
            $content = str_replace(
                "[namespace]",
                $this->daoFactory->getModelNamespace($this->connectionName, $this->bundle),
                str_replace("[modelName]",
                    static::camelize($dbTableName),
                    static::$modelTemplate
                )
            );
            file_put_contents($modelFile, $content);
        }

        return $this;
    }
}