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

class LayersExecuteCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('sebk:small-orm:layers-execute')
            ->setDescription('Execute missing layers')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $layersService = $this->getContainer()->get("sebk_small_orm_layers");
        $layersService->execute();
    }
}