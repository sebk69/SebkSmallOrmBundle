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
        $treeBuilder = new TreeBuilder("sebk_small_orm");

        $treeBuilder->getRootNode()
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
                                ->ifNotInArray(array('swoole-mysql', 'swoole-redis', 'redis', 'mysql', 'none'))
                                    ->thenInvalid('Invalid database driver "%s"')
                                ->end()
                            ->end()
                            ->scalarNode('host')
                                ->defaultValue('localhost')
                            ->end()
                            ->scalarNode('encoding')
                                ->defaultValue('utf8')
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
                            ->scalarNode('vendor')
                                ->defaultValue("false")
                            ->end()
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
                                        ->arrayNode('remove_tables_namespaces')
                                            ->prototype('scalar')
                                            ->end()
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