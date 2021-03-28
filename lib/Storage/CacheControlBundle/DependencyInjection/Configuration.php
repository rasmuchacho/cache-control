<?php

namespace Storage\CacheControlBundle\DependencyInjection;

use Storage\CacheControlBundle\Reader\CacheValueOverrider;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @author Nomenjanahary Randriamahefa <rasmuchacho@gmail.com>
 */
class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('storage_cache_control');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->enumNode('override_strategy')
                    ->values([
                        CacheValueOverrider::OVERRIDE_MERGE,
                        CacheValueOverrider::OVERRIDE_REPLACE,
                    ])
                    ->defaultValue(CacheValueOverrider::OVERRIDE_REPLACE)
                ->end()
                ->arrayNode('exclude_status')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('default_cache')
                    ->children()
                        ->scalarNode('maxAge')->defaultNull()->end()
                        ->scalarNode('public')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('override')
                    ->arrayPrototype()
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
