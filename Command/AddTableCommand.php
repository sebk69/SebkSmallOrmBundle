<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015-2017 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Command;

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

class AddTableCommand extends Command
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
            ->setName('sebk:small-orm:add-table')
            ->setDescription('Add dao for database table')
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

        // ask user...
        $helper = $this->getHelper('question');

        // for connection...
        $question = new Question('Connection ['.$defaultConnection.'] ? ', $defaultConnection);
        $connectionName = $helper->ask($input, $output, $question);

        // bundle...
        $question = new Question('Bundle ['.$defaultBundle.'] ? ', $defaultBundle);
        $bundle = $helper->ask($input, $output, $question);

        // and table
        $question = new Question('Database table [all] ? ', 'all');
        $dbTableName = $helper->ask($input, $output, $question);

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
        shell_exec("bin/console sebk:small-orm:add-methods-bloc-comment");

        return 0;
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
