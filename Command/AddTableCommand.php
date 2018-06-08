<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015-2017 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Command;

use Sebk\SmallOrmBundle\Generator\Config;
use Sebk\SmallOrmBundle\Generator\DbGateway;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Sebk\SmallOrmBundle\Generator\FileParser;
use Symfony\Component\Console\Question\Question;

class AddTableCommand extends ContainerAwareCommand
{

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
        foreach($this->getContainer()->getParameter("sebk_small_orm.bundles") as $defaultBundle => $parms) {
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
            $connection = $this->getContainer()->get("sebk_small_orm_connections")->get($connectionName);
            $dbGateway = new DbGateway($connection);

            foreach($dbGateway->getTables() as $dbTableName) {
                if($dbTableName != "_small_orm_layers") {
                    $this->addTable($connectionName, $bundle, $dbTableName);
                }
            }
        }
    }

    /*
     * Add table to bundle
     * @param $connectionName
     * @param $bundle
     * @param $dbTableName
     */
    protected function addTable($connectionName, $bundle, $dbTableName)
    {
        $daoGenrator = $this->getContainer()->get("sebk_small_orm_generator");
        $daoGenrator->setParameters($connectionName, $bundle);
        //try {
            $daoGenrator->recomputeFilesForTable($dbTableName);

            $config = new Config($bundle, $connectionName, $this->getContainer());
            $config->addTable($dbTableName);
        //} catch(\Exception $e) {

        //}
    }
}