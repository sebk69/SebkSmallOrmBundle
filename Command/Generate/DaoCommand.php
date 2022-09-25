<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Command\Generate;

use Psr\Container\ContainerInterface;
use Sebk\SmallOrmCore\Factory\Connections;
use Sebk\SmallOrmCore\Generator\Config;
use Sebk\SmallOrmCore\Generator\DaoGenerator;
use Sebk\SmallOrmCore\Generator\DbGateway;
use Sebk\SmallOrmCore\Generator\Selector;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DaoCommand extends Command
{
    private $selectors;
    private $folders;
    private $connections;
    private $daoGenerator;
    private $container;

    public function __construct(array $generatorConfig, Connections $connections, DaoGenerator $daoGenerator, ContainerInterface $container)
    {
        $this->selectors = $generatorConfig['selectors'];
        $this->folders = $generatorConfig['folders'];
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
            ->addOption("connection", null, InputOption::VALUE_REQUIRED, "Connection to retreive table", 'default')
            ->addOption("selector", null, InputOption::VALUE_REQUIRED, "Selector to determine namespaces", null)
            ->addOption("table", null, InputOption::VALUE_REQUIRED, "Table for DAO ('all' for all database tables) ", null)
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get first selector as default
        foreach($this->selectors as $defaultSelector => $parms) {
            break;
        }

        // get connection
        if ($input->getOption("connection") == null) {
            $connectionName = $defaultConnection;
        } else {
            $connectionName = $input->getOption("connection");
        }

        // get selector
        if ($input->getOption("selector") == null) {
            $selector = new Selector($this->folders, $this->selectors[$defaultSelector]);
        } else {
            $selector = new Selector($this->folders, $this->selectors[$input->getOption("selector")]);
        }

        // get table
        if ($input->getOption("table") == null) {
            throw new \Exception("The table must be specified ('all' for create DAO for all tables)");
        } else {
            $dbTableName = $input->getOption("table");
        }

        // add selected tables
        if($dbTableName != "all") {
            $this->addTable($connectionName, $selector, $dbTableName);
        } else {
            $connection = $this->connections->get($connectionName);
            $dbGateway = new DbGateway($connection);

            foreach($dbGateway->getTables() as $dbTableName) {
                if($dbTableName != "_small_orm_layers") {
                    $this->addTable($connectionName, $selector, $dbTableName);
                }
            }
        }

        return static::SUCCESS;
    }

    /*
     * Add table to bundle
     * @param $connectionName
     * @param $bundle
     * @param $dbTableName
     */
    protected function addTable(string $connectionName, Selector $selector, string $dbTableName)
    {
        /** @var DaoGenerator $daoGenrator */
        $daoGenrator = $this->daoGenerator;
        $daoGenrator->setParameters($connectionName, $selector);
        $daoGenrator->recomputeFilesForTable($dbTableName);
    }
}
