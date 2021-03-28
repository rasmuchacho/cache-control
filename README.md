Installation
============

```
composer require nomenjanahary/cache-control
```

### Configuration
```yaml
storage_cache_control:
  exclude_status:
    - "5xx"
    - "4xx"
  default_cache:
    maxAge: 3600
    public: true
  # merge, replace
  override_strategy: merge
  override:
    "T23H":
      maxAge: 7200
      public: true
    "1D":
      maxAge: 63200
      public: true
    "1Y":
      maxAge: 9037200
      public: true
```


### Specify cache control on all controller
```php
use Storage\CacheControlBundle\Annotation as StorageCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * ...
 *
 * @StorageCache\Cache({
 *     "maxAge": 200,
 *     "sharedMaxAge": 200,
 *     "public": true,
 * })
 */
class MyController extends AbstractController
{

}
```

### Specify cache control on an action
```php
use Storage\CacheControlBundle\Annotation as StorageCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MyController extends AbstractController
{

    /**
     * myAction
     * ...
     *
     * @StorageCache\Cache({
     *     "maxAge": 200,
     *     "sharedMaxAge": 200,
     *     "public": true,
     * })
     */
    public function myAction()
    {
    
    }

}
```
## Override cache control
### Override in regard to last update time
```php
    /**
     * ...
     *
     * @StorageCache\Cache({
     *     "maxAge": 200,
     *     "sharedMaxAge": 200,
     *     "public": true,
     * },
     * override={
     *     "1Y": {
     *         "maxAge": 90000,
     *         "sharedMaxAge": 90000,
     *         "public": true
     *     },
     *     "3M": {
     *         "maxAge": 30000,
     *         "sharedMaxAge": 30000,
     *         "public": true
     *     }
     * })
     */
```
### Override from global configuration to latest specified one
`yaml` is overridable by `controller annotation` which is overridable by `action annotation`
