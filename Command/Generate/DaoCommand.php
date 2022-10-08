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

        // get selector
        if ($input->getOption("selector") == null) {
            $selector = new Selector($this->folders, $this->selectors[$defaultSelector]);
        } else {
            $selector = new Selector($this->folders, $this->selectors[$input->getOption("selector")]);
        }

        // get connection
        if ($input->getOption("connection") == null) {
            if ($this->selectors->getSelection()->getConnection() == null) {
                $connectionName = $defaultConnection;
            } else {
                $connectionName = $this->selectors->getSelection()->getConnection();
            }
        } else {
            $connectionName = $input->getOption("connection");
        }

        // get table
        $table = $input->getOption("table");

        // add selected tables
        $dbGateway = new DbGateway($this->connections->get($connectionName));

        $tablesToAdd = [];
        foreach($dbGateway->getTables() as $dbTableName) {
            if ($selector->getConnection() != null && $selector->getConnection() != $connectionName) {
                continue;
            }
            if (!$selector->isTableInSelection($dbTableName)) {
                continue;
            }
            if ($table != null && $dbTableName != $table) {
                continue;
            }
            $tablesToAdd[] = $dbTableName;
        }

        $this->daoGenerator->setParameters($connectionName, $selector);

        foreach ($tablesToAdd as $table) {
            $this->daoGenerator->createDaoFile($table);
        }
        foreach ($tablesToAdd as $table) {
            $this->daoGenerator->recomputeFilesForTable($table);
        }

        return static::SUCCESS;
    }

}
