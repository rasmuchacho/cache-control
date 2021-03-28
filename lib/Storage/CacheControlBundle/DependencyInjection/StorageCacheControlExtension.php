<?php

namespace Storage\CacheControlBundle\DependencyInjection;

use Storage\CacheControlBundle\Reader\CacheValueOverrider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Class StorageCacheControlExtension
 *
 * @author Nomenjanahary Randriamahefa <rasmuchacho@gmail.com>
 */
class StorageCacheControlExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('storage_cache_control.exclude_status', array_key_exists('exclude_status', $config) ? $config['exclude_status']: []);
        $container->setParameter('storage_cache_control.default_cache', array_key_exists('default_cache', $config) ? $config['default_cache']: []);
        $container->setParameter('storage_cache_control.override', array_key_exists('override', $config) ? $config['override']: []);
        $container->setParameter('storage_cache_control.override_strategy', $config['override_strategy']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }
}
