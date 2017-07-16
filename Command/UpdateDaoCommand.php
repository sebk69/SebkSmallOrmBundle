<?php
/**
 * This file is a part of SebkSmallUserBundle
 * Copyright 2015-2017 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Command;

use Sebk\SmallOrmBundle\Generator\Config;
use Sebk\SmallOrmBundle\Generator\ConfigCollection;
use Sebk\SmallOrmBundle\Generator\DbGateway;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Sebk\SmallOrmBundle\Generator\FileParser;
use Symfony\Component\Console\Question\Question;

class UpdateDaoCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('sebk:small-orm:update-dao')
            ->setDescription('Update all dao from db')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get first bundle as default
        $bundlesConfig = $this->getContainer()->getParameter("sebk_small_orm.bundles");
        foreach($bundlesConfig as $defaultBundle => $parms) {
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

        // for connection
        $question = new Question('Connection ['.$defaultConnection.'] ? ', $defaultConnection);
        $connectionName = $helper->ask($input, $output, $question);

        $generatorConfigs = new ConfigCollection($connectionName, $this->getContainer());
        $generatorConfigs->loadConfigs();

        foreach($generatorConfigs->getAllConfiguredTables() as $record) {
            if(isset($bundlesConfig[$record["bundle"]]["vendor"]) && $bundlesConfig[$record["bundle"]]["vendor"] == "true") {
                continue;
            }
            $this->updateTable($connectionName, $record["bundle"], $record["table"]);
        }
    }

    /*
     * Update table to bundle
     * @param $connectionName
     * @param $bundle
     * @param $dbTableName
     */
    protected function updateTable($connectionName, $bundle, $dbTableName)
    {
        $daoGenrator = $this->getContainer()->get("sebk_small_orm_generator");
        $daoGenrator->setParameters($connectionName, $bundle);
        $daoGenrator->recomputeFilesForTable($dbTableName);
    }
}