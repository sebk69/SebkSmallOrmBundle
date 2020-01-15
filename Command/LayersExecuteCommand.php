<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015-2017 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Command;

use Sebk\SmallOrmBundle\Generator\Config;
use Sebk\SmallOrmBundle\Generator\DbGateway;
use Sebk\SmallOrmBundle\Layers\Layers;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Sebk\SmallOrmBundle\Generator\FileParser;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Container;

class LayersExecuteCommand extends Command
{
    private $layersService;

    public function __construct(Layers $layersService)
    {
        $this->layersService = $layersService;

        parent::__construct();
    }

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
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->layersService->execute();

        return 0;
    }
}