<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Command\Generate;

use Sebk\SmallOrmCore\Generator\DaoGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Sebk\SmallOrmBundle\Generator\FileParser;
use Symfony\Component\Console\Question\Question;

class ModelAutocompletionCommand extends Command
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function configure()
    {
        $this
            ->setName('sebk:small-orm:generate:model-autocompletion')
            ->setDescription('Add methods bloc comment in model')
            ->addOption("connection", null, InputOption::VALUE_REQUIRED, "Connection to retreive table", null)
            ->addOption("bundle", null, InputOption::VALUE_REQUIRED, "Bundle in which the DAO will be created", null)
            ->addOption("dao", null, InputOption::VALUE_REQUIRED, "DAO name (all daos if not specified) ", "all")
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

        // and dao
        if ($input->getOption("dao") == null) {
            $dao = "all";
        } else {
            $dao = $input->getOption("dao");
        }

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

        return 0;
    }
}