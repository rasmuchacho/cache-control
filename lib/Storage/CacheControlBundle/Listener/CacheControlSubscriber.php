<?php


namespace Storage\CacheControlBundle\Listener;

use Doctrine\Common\Annotations\AnnotationReader;
use Storage\CacheControlBundle\Annotation\Cache;
use Storage\CacheControlBundle\Reader\CacheValueOverrider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class CacheControlSubscriber
 *
 * @author Nomenjanahary Randriamahefa <rasmuchacho@gmail.com>
 */
class CacheControlSubscriber implements EventSubscriberInterface
{

    /**
     * CacheControlSubscriber router
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * CacheControlSubscriber excludeStatus
     *
     * @var array
     */
    private $excludeStatus;

    /**
     * CacheControlSubscriber overrideDefinition
     *
     * @var array
     */
    private $overrideDefinition = [];

    /**
     * CacheControlSubscriber defaultCache
     *
     * @var array
     */
    private $defaultCache = [];

    /**
     * CacheControlSubscriber overrideStrategy
     *
     * @var string
     */
    private $overrideStrategy;

    /**
     * CacheControlSubscriber constructor.
     *
     * @param RouterInterface $router
     * @param array           $excludeStatus
     * @param string          $overrideStrategy
     * @param array           $defaultCache
     * @param array           $overrideDefinition
     */
    public function __construct(RouterInterface $router, string $overrideStrategy, array $excludeStatus = [], array $defaultCache = [], array $overrideDefinition = [])
    {
        $this->router = $router;
        $this->excludeStatus = $excludeStatus;
        $this->defaultCache = $defaultCache;
        $this->overrideDefinition = $overrideDefinition;
        $this->overrideStrategy = $overrideStrategy;
    }

    /**
     * getSubscribedEvents
     *
     * @return array|string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['applyCache', -10000],
        ];
    }

    /**
     * applyCache
     *
     * @param ResponseEvent $responseEvent
     *
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function applyCache(ResponseEvent $responseEvent)
    {
        if (!$responseEvent->isMasterRequest()) {
            return;
        }

        $request = $responseEvent->getRequest();
        $controller = $request->attributes->get('_controller');

        [$controller, $action] = explode('::', $controller.'::');
        if (!class_exists($controller)) {
            return;
        }

        $reflection = new \ReflectionClass($controller);

        $cacheValueOverrider = new CacheValueOverrider();
        $annotationReader = new AnnotationReader();
        $cacheValueOverrider
            ->addCacheConfig($annotationReader->getMethodAnnotation($reflection->getMethod($action), Cache::class), CacheValueOverrider::CACHE_CONFIG_LEVEL_HIGHEST)
            ->addCacheConfig($annotationReader->getClassAnnotation($reflection, Cache::class), CacheValueOverrider::CACHE_CONFIG_LEVEL_LOW)
            ->addCacheConfig(Cache::fromConfig([
                'value' => $this->defaultCache,
                'override' => $this->overrideDefinition,
                'excludeStatus' => $this->excludeStatus,
                'overrideStrategy' => $this->overrideStrategy,
            ]), CacheValueOverrider::CACHE_CONFIG_LEVEL_LOWEST);

        $cache = $cacheValueOverrider->measureFromRequest($request);

        // Apply cache rule
        $this->applyCacheRule($responseEvent->getResponse(), $cache);
    }

    /**
     * applyCache
     *
     * @param Response $response
     * @param Cache    $cache
     *
     * @return $this
     *
     * @throws \Exception
     */
    protected function applyCacheRule(Response $response, Cache $cache): self
    {
        $cacheRule = $cache->value;

        // Remove persisting cache control values
        $response->headers->removeCacheControlDirective('must-revalidate');
        $response->headers->removeCacheControlDirective('max-age');
        $response->headers->remove('last-modified');

        foreach ($cacheRule as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);
            $setMethod = 'set'.ucfirst($key);
            switch ($normalizedKey) {
                case 'must-revalidate':
                    if ((boolean) $value) {
                        $response->headers->addCacheControlDirective($normalizedKey);
                    }

                    break;
                default:
                    if (is_bool($value) && $value) {
                        $response->{$setMethod}();
                    } elseif (!is_bool($value) && !empty($value)) {
                        $response->{$setMethod}($value);
                    }

                    break;
            }
        }

        return $this;
    }

    /**
     * normalizeKey
     *
     * @param string $key
     *
     * @return string
     */
    protected function normalizeKey($key)
    {
        return preg_replace_callback(',(?<camel>[A-Z]),', function ($match)
        {
            return '-'.strtolower($match['camel']);
        }, lcfirst($key));
    }
}
