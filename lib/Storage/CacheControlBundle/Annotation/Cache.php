<?php

namespace Storage\CacheControlBundle\Annotation;

use Storage\CacheControlBundle\Constraint\TimeMeasurableInterface;
use Storage\CacheControlBundle\Reader\CacheValueOverrider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Cache
 *
 * @author Nomenjanahary Randriamahefa <rasmuchacho@gmail.com>
 *
 * @Annotation
 *
 * @Target({"CLASS", "METHOD"})
 */
class Cache
{
    /**
     * Cache value
     *
     * @var array
     */
    public $value = [];

    /**
     * Cache override
     *
     * @var array
     */
    public $override = [];

    /**
     * Cache excludeStatus
     *
     * @var array
     */
    public $excludeStatus = [];

    /**
     * Cache timestampedParameter
     *
     * @var string
     */
    public $timestampedParameter = null;

    /**
     * Cache overrideStrategy
     *
     * @var string
     */
    public $overrideStrategy = null;

    /**
     * Cache lastModified
     *
     * @var \DateTimeInterface|null
     */
    public $lastModified;

    /**
     * Cache get value
     *
     * @return array
     */
    public function getValue(): ?array
    {
        return $this->value;
    }

    /**
     * Cache set value
     *
     * @param array|null $value
     *
     * @return self
     */
    public function setValue(?array $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Cache get override
     *
     * @return array
     */
    public function getOverride(): ?array
    {
        return $this->override;
    }

    /**
     * Cache set override
     *
     * @param array|null $override
     *
     * @return self
     */
    public function setOverride(?array $override): self
    {
        $this->override = $override;

        return $this;
    }

    /**
     * Cache get excludeStatus
     *
     * @return array
     */
    public function getExcludeStatus(): array
    {
        return $this->excludeStatus;
    }

    /**
     * Cache set excludeStatus
     *
     * @param array $excludeStatus
     *
     * @return self
     */
    public function setExcludeStatus(array $excludeStatus): self
    {
        $this->excludeStatus = $excludeStatus;

        return $this;
    }

    /**
     * Cache get timestampedParameter
     *
     * @return string|null
     */
    public function getTimestampedParameter(): ?string
    {
        return $this->timestampedParameter;
    }

    /**
     * Cache set timestampedParameter
     *
     * @param string|null $timestampedParameter
     *
     * @return self
     */
    public function setTimestampedParameter(?string $timestampedParameter): self
    {
        $this->timestampedParameter = $timestampedParameter;

        return $this;
    }

    /**
     * measureFromRequest
     *
     * @param Request $request
     *
     * @return $this
     */
    public function measureFromRequest(Request $request)
    {
        if (empty($this->timestampedParameter) || !$request->attributes->has($this->timestampedParameter)) {
            return $this;
        }

        /** @var TimeMeasurableInterface $context */
        if (!($context = $request->attributes->get($this->timestampedParameter)) instanceof TimeMeasurableInterface
            && (!is_object($context) || !method_exists($context, 'getLastModified'))) {
            return $this;
        }

        $this->lastModified = $context->getLastModified();

        return $this;
    }

    /**
     * Cache get lastModified
     *
     * @return \DateTimeInterface|null
     */
    public function getLastModified(): ?\DateTimeInterface
    {
        return $this->lastModified;
    }

    /**
     * Cache set lastModified
     *
     * @param \DateTimeInterface|null $lastModified
     *
     * @return self
     */
    public function setLastModified(?\DateTimeInterface $lastModified): self
    {
        $this->lastModified = $lastModified;

        return $this;
    }

    /**
     * fromConfig
     *
     * @param array $config
     *
     * @return Cache
     */
    public static function fromConfig(array $config): Cache
    {
        $cache = new Cache();
        foreach (['value', 'override', 'excludeStatus', 'overrideStrategy'] as $property) {
            if (array_key_exists($property, $config)) {
                $cache->{$property} = $config[$property];
            }
        }

        return $cache;
    }

    /**
     * applyOverride
     *
     * @param string|null $overrideStrategy
     *
     * @return Cache
     *
     * @throws \Exception
     */
    public function applyInternalOverride(string $overrideStrategy = null): Cache
    {
        if (null === $overrideStrategy) {
            $overrideStrategy = $this->overrideStrategy;
        }
        $now = new \DateTime();

        $rule = $this->value;

        if (($lastModified = $this->getLastModified()) instanceof \DateTimeInterface) {
            // Override by specific definition
            foreach ($this->override as $time => $def) {
                $currentLastModified = clone $lastModified;
                $currentLastModified->add(new \DateInterval('P'.$time));

                if ($currentLastModified <= $now) {
                    $rule = CacheValueOverrider::OVERRIDE_REPLACE === $overrideStrategy ?
                        $def :
                        array_merge($rule, $def);

                    $now = $currentLastModified;
                }
            }
        }

        $result = new Cache();
        $result->value = array_merge($rule, [
            'lastModified' => $this->lastModified,
        ]);
        $result->overrideStrategy = $this->overrideStrategy;

        return $result;
    }

    /**
     * applyOverride
     *
     * @param Cache  $cache
     * @param string $overrideStrategy
     */
    public function applyOverride(Cache $cache, string $overrideStrategy): void
    {
        $this->value = CacheValueOverrider::OVERRIDE_REPLACE === $overrideStrategy ?
            $cache->value :
            array_merge($this->value, $cache->value);
    }
}
