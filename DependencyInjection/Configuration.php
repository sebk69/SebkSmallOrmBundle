<?php

namespace Sebk\SmallOrmBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('sebk_small_orm');

        $rootNode
            ->children()
                ->scalarNode('default_connection')
                    ->defaultValue('default')
                ->end()
                ->arrayNode('connections')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')
                                ->isRequired()
                                ->validate()
                                ->ifNotInArray(array('mysql'))
                                    ->thenInvalid('Invalid database driver "%s"')
                                ->end()
                            ->end()
                            ->scalarNode('host')
                                ->defaultValue('localhost')
                            ->end()
                            ->scalarNode('database')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('user')->end()
                            ->scalarNode('password')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('bundles')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('connections')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('dao_namespace')
                                            ->defaultValue("Dao")
                                        ->end()
                                        ->scalarNode('model_namespace')
                                            ->defaultValue("Model")
                                        ->end()
                                        ->scalarNode('validator_namespace')
                                            ->defaultValue("Validator")
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        
        return $treeBuilder;
    }
}