Installation
============

```
composer require rasmuchacho/cache-control
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


### Specify cache control for the controller
```php
use Storage\CacheControlBundle\Annotation as StorageCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
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

### Specify cache control on a specified action
```php
use Storage\CacheControlBundle\Annotation as StorageCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MyController extends AbstractController
{

    /**
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
### Override priority
`yaml` is overridable by `controller annotation` which is overridable by `action annotation`

### Override in regard to last update time
```php
    /**
     * @StorageCache\Cache({
     *     "maxAge": 3600,
     *     "public": true
     * },
     * override={
     *     "1D": {
     *         "maxAge": 259200,
     *     },
     *     "3D": {
     *         "maxAge": 2592000,
     *     }
     * })
     */
```
To select the right override values, it checks on possible value list by comparing the value of `lastModified` of returned `Response`

```
    public function show()
    {
        return (new Response())
            ->setLastModified(new \DateTime('2021-03-29 00:00:00'));
    }
```
We can have these results :

* Before 30/03/2021

```
Cache-Control: max-age=3600, public
```

* After 30/03/2021 and before 01/04/2021

```
Cache-Control: max-age=259200, public
```

* After 01/04/2021

```
Cache-Control: max-age=25920000, public
```

### Specify which parameter is taken as reference
Ensure that this object has method `getLastModified()` that returns `\DateTimeInterface` or implements `Storage\CacheControlBundle\Constraint\TimeMeasurableInterface`

```php

use Storage\CacheControlBundle\Constraint\TimeMeasurableInterface;

class BlogArticle implements TimeMeasurableInterface
{
    public function getLastModified(): ?\DateTimeInterface
    {
        // return article last modified
    }
}
```
Determine cache control by the request passed parameter

```php
    /**
     * @StorageCache\Cache({
     *     "maxAge": 3600,
     *     "public": true,
     *     "vary": "Accept"
     * },
     * timestampedParameter="article",
     * override={
     *     "1D": {
     *         "maxAge": 259200,
     *     },
     *     "3D": {
     *         "maxAge": 2592000,
     *     }
     * })
     *
     * @Route("/{article}", name="blog_article_show")
     */
    public function show(BlogArticle $article)
    {
    }
```
### Override strategy
You can use two kind of override strategy `merge`to merge config and `replace` to just replace configuration
Use of override strategy :

__Merge__ (you can also use constant`Storage\CacheControlBundle\Reader\CacheValueOverrider::OVERRIDE_MERGE`)

* Yaml

```yaml
storage_cache_control:
  default_cache:
    maxAge: 3600
    public: true
  override_strategy: merge
  override:
    "1D":
      maxAge: 259200
```

* Annotation on a controller or on an action

```php
    /**
     * @StorageCache\Cache({
     *     "maxAge": 3600
     *     "public": true
     * },
     * overrideStrategy="merge",
     * override={
     *     "1D": {
     *         "maxAge": 259200
     *     }
     * })
```

**_Result_** :

We can have this cache-control value

```
Cache-Control: max-age=3600, public
```

And after one day

```
Cache-Control: max-age=259200, public
```


__Replace__ (you can also use constant `Storage\CacheControlBundle\Reader\CacheValueOverrider::OVERRIDE_REPLACE`)

* Yaml

```yaml
storage_cache_control:
  default_cache:
    mustRevalidate: true
  override_strategy: replace
  override:
    "1D":
      maxAge: 259200
      public: true
```

* Annotation on a controller or on an action

```php
    /**
     * @StorageCache\Cache({
     *     "mustRevalidate": true
     * },
     * overrideStrategy="replace",
     * override={
     *     "1D": {
     *         "maxAge": 259200,
     *         "public": true
     *     }
     * })
```

**_Result_** :

We can have this cache-control value

```
Cache-Control: must-revalidate, private
```

And after one day

```
Cache-Control: max-age=259200, public
```
