<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Command\Generate;

use Sebk\SmallOrmCore\Factory\Connections;
use Sebk\SmallOrmCore\Generator\Config;
use Sebk\SmallOrmCore\Generator\DaoGenerator;
use Sebk\SmallOrmCore\Generator\DbGateway;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DaoCommand extends Command
{
    private $bundles;
    private $connections;
    private $daoGenerator;
    private $container;

    public function __construct(array $bundles, Connections $connections, DaoGenerator $daoGenerator, $container)
    {
        $this->bundles = $bundles;
        $this->connections = $connections;
        $this->daoGenerator = $daoGenerator;
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('sebk:small-orm:generate:dao')
            ->setDescription('Add dao for database table')
            ->addOption("connection", null, InputOption::VALUE_REQUIRED, "Connection to retreive table", null)
            ->addOption("bundle", null, InputOption::VALUE_REQUIRED, "Bundle in which the DAO will be created", null)
            ->addOption("table", null, InputOption::VALUE_REQUIRED, "Table for DAO ('all' for all database tables) ", null)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get first bundle as default
        foreach($this->bundles as $defaultBundle => $parms) {
            break;
        }

        // get default connection
        $defaultConnection = null;
        foreach($parms["connections"] as $connection => $content) {
            if($defaultConnection === null || $connection == "default") {
                $defaultConnection = $connection;
            }
        }

        // get connection
        if ($input->getOption("connection") == null) {
            $connectionName = $defaultConnection;
        } else {
            $connectionName = $input->getOption("connection");
        }

        // get bundle
        if ($input->getOption("bundle") == null) {
            $bundle = $defaultBundle;
        } else {
            $bundle = $input->getOption("bundle");
        }

        // get table
        if ($input->getOption("table") == null) {
            throw new \Exception("The table must be specified ('all' for create DAO for all tables)");
        } else {
            $dbTableName = $input->getOption("table");
        }

        // add selected tables
        if($dbTableName != "all") {
            $this->addTable($connectionName, $bundle, $dbTableName);
        } else {
            $connection = $this->connections->get($connectionName);
            $dbGateway = new DbGateway($connection);

            foreach($dbGateway->getTables() as $dbTableName) {
                if($dbTableName != "_small_orm_layers") {
                    $this->addTable($connectionName, $bundle, $dbTableName);
                }
            }
        }

        $output->writeln("Generating completion helper...");
        shell_exec("bin/console sebk:small-orm:generate:model-autocompletion " .
            "--connection " . $connectionName . " --bundle " . $bundle . " " .
            ($dbTableName != 'all' ? " --dao " . $this->daoGenerator->getDaoClassName($dbTableName) : ""));

        return static::SUCCESS;
    }

    /*
     * Add table to bundle
     * @param $connectionName
     * @param $bundle
     * @param $dbTableName
     */
    protected function addTable($connectionName, $bundle, $dbTableName)
    {
        /** @var DaoGenerator $daoGenrator */
        $daoGenrator = $this->daoGenerator;
        $daoGenrator->setParameters($connectionName, $bundle);
        $daoGenrator->recomputeFilesForTable($dbTableName);
        $config = new Config($bundle, $connectionName, $this->container);
        $config->addTable($dbTableName);
    }
}
