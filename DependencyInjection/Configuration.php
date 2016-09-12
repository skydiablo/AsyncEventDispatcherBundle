<?php

namespace AsyncEventDispatcherBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('async_event_dispatcher');

        $rootNode
            ->children()
                ->arrayNode('queue')
                    ->children()
                        ->arrayNode('awssqs')
                            ->children()
                                ->scalarNode('queue_url')->end()
                                ->integerNode('long_polling_timeout')->end()
                                ->scalarNode('sqs_client')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();


        return $treeBuilder;
    }
}
