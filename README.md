# SmallOrmBundle
Small ORM for symfony app

## Install

Require the bundle with composer:
```
composer require sebk/small-orm-bundle "~1.0"
```

Register the bundle in `app/AppKernel.php`:

``` php
public function registerBundles()
{
    return array(
        // ...
        new Sebk\SmallOrmBundle\SebkSmallOrmBundle(),
    );
}
```

## Documentation

See [documentation]: https://small-iceberg.dev/small-orm
