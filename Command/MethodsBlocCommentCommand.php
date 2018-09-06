<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015-2017 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Command;

use Sebk\SmallOrmBundle\Generator\Config;
use Sebk\SmallOrmBundle\Generator\DaoGenerator;
use Sebk\SmallOrmBundle\Generator\DbGateway;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Sebk\SmallOrmBundle\Generator\FileParser;
use Symfony\Component\Console\Question\Question;

class MethodsBlocCommentCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('sebk:small-orm:add-methods-bloc-comment')
            ->setDescription('Add methods bloc comment in model')
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
        $question = new Question('Dao [all] ? ', 'all');
        $dao = $helper->ask($input, $output, $question);

        /** @var DaoGenerator $daoGenrator */
        $daoGenrator = $this->getContainer()->get("sebk_small_orm_generator");
        $daoGenrator->setParameters($connectionName, $bundle);
        if($dao != "all") {
            // Single file
            $daoGenrator->createAtModelMethods($dao);
        } else {
            // All files
            foreach(scandir($this->getContainer()->get("sebk_small_orm_dao")->getDaoDir($bundle, $connectionName)) as $file) {
                if(substr($file, strlen($file) - 4) == ".php") {
                    $dao = substr($file, 0, strlen($file) - 4);
                    $daoGenrator->createAtModelMethods($dao);
                }
            }
        }
    }
}