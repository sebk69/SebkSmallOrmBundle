# SmallOrmBundle
Small ORM for symfony app

# Migrated

This lib has been migrated to [framagit](https://framagit.org/small) project.

A new composer package is available at https://packagist.org/packages/small/orm-bundle

Future commits will be done on framagit.

## Install

Require the bundle with composer:
```
composer require sebk/small-orm-bundle "~2.0"
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

See [documentation](https://github.com/sebk69/small-orm-doc)
