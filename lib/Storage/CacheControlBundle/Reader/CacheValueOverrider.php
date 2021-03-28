<?php


namespace Storage\CacheControlBundle\Reader;

use Storage\CacheControlBundle\Annotation\Cache;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CacheValueOverrider
 *
 * @author Nomenjanahry Randriamahefa <rasmuchacho@gmail.com>
 */
class CacheValueOverrider
{
    const OVERRIDE_MERGE = 'merge';

    const OVERRIDE_REPLACE = 'replace';

    const CACHE_CONFIG_LEVEL_LOWEST = 0;

    const CACHE_CONFIG_LEVEL_LOW = 1;

    const CACHE_CONFIG_LEVEL_HIGHEST = 2;

    /**
     * CacheValueOverrider cacheConfig
     *
     * @var array
     */
    private $cacheConfig = [];

    /**
     * CacheValueOverrider overrideStrategy
     *
     * @var string|null
     */
    private $overrideStrategy;

    /**
     * CacheValueOverrider timestampedParameter
     *
     * @var string|null
     */
    private $timestampedParameter = null;

    /**
     * addCacheConfig
     *
     * @param Cache|null $cache
     * @param int        $level
     *
     * @return $this
     */
    public function addCacheConfig(?Cache $cache, int $level): self
    {
        if (null === $cache) {
            return $this;
        }

        $this->cacheConfig[$level] = $cache;

        if (null !== $cache->getTimestampedParameter()) {
            $this->timestampedParameter = $cache->getTimestampedParameter();
        }

        return $this;
    }

    /**
     * measureFromRequest
     *
     * @param Request $request
     *
     * @return Cache
     *
     * @throws \Exception
     */
    public function measureFromRequest(Request $request): Cache
    {
        if (null !== $this->timestampedParameter) {
            /** @var Cache $config */
            foreach ($this->cacheConfig as $config) {
                $config->setTimestampedParameter($this->timestampedParameter);
                $config->measureFromRequest($request);
            }
        }

        return $this->applyOverride();
    }

    /**
     * applyOverride
     *
     * @return Cache
     *
     * @throws \Exception
     */
    protected function applyOverride(): Cache
    {
        $overrideStrategy = self::OVERRIDE_MERGE;

        $cacheConfig = $this->cacheConfig;
        ksort($cacheConfig);
        /** @var Cache $cache */
        foreach ($cacheConfig as $cache) {
            if (null !== $cache->overrideStrategy) {
                $overrideStrategy = $cache->overrideStrategy;
            }
        }

        /** @var Cache $cache */
        foreach ($cacheConfig as &$cache) {
            $cache = $cache->applyInternalOverride($overrideStrategy);
        }

        /** @var Cache $cacheValue */
        $cacheValue = array_shift($cacheConfig);
        foreach ($cacheConfig as $highLevelCache) {
            $cacheValue->applyOverride($highLevelCache, $overrideStrategy);
        }

        return $cacheValue;
    }
}
