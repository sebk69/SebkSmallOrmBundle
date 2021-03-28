<?php

/**
 * This file is a part of SebkSmallOrmBundle
 * Copyright 2015-2021 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Factory;


use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FakeContainer
 * @package Sebk\SmallOrmBundle\Factory
 */
class FakeContainer implements ContainerInterface
{
    const DAO_FACTORY_SERVICE = "sebk_small_orm_dao";

    /** @var ContainerInterface $container */
    protected $container;
    /** @var array $isolatedService */
    protected $isolatedService = [];
    /** @var array $isolatedParameters */
    protected $isolatedParameters = [];

    /**
     * FakeContainer constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->isolatedService[self::DAO_FACTORY_SERVICE] = new Dao(
            $container->get("sebk_small_orm_connections"),
            $container->getParameter("sebk_small_orm.bundles"),
            $this
        );
    }

    /**
     * Isolate fake container
     */
    public function isolate()
    {
        $this->container = null;
    }

    /**
     * Add a service to use it in isolated environment
     * @param $id
     */
    public function addServiceForIsolation($id)
    {
        if ($id == self::DAO_FACTORY_SERVICE) {
            throw new \Exception("Can't override dao factory in fake container");
        }

        $this->isolatedService[$id] = $this->container->get($id);
    }

    /**
     * Add a dao for isolated environment
     * @param $bundle
     * @param $model
     * @return mixed
     * @throws ConfigurationException
     * @throws DaoNotFoundException
     * @throws \ReflectionException
     */
    public function addDaoForIsolation($bundle, $model)
    {
        return $this->isolatedService[self::DAO_FACTORY_SERVICE]->get($bundle, $model);
    }

    /**
     * Add a parameter tu use it in isolated environment
     * @param $name
     */
    public function addIsolatedParameter($name)
    {
        $this->isolatedParameters[$name] = $this->container->getParameter($name);
    }

    /**
     * Set a service in container and add it for isolated environment
     * @param string $id
     * @param object $service
     * @throws \Exception
     */
    public function set($id, $service)
    {
        if ($id == self::DAO_FACTORY_SERVICE) {
            throw new \Exception("Can't override dao factory in fake container");
        }

        if ($this->container !== null) {
            $this->container->set($id, $service);
            $this->addServiceForIsolation($id, $service);
        } else {
            throw new \Exception("The container has been isolated");
        }
    }

    /**
     * Get a service in isolated environment
     * @param string $id
     * @param int $invalidBehavior
     * @return mixed|object
     * @throws \Exception
     */
    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if ($this->container === null) {
            if (isset($this->isolatedService[$id])) {
                return $this->isolatedService[$id];
            } else {
                throw new \Exception("This service has not been added for isolation");
            }
        } else {
            throw new \Exception("The fake container has not been isolated yet. Impossible to get service");
        }
    }

    /**
     * Has a service added in isolated environment
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->isolatedService[$id]);
    }

    /**
     * Has a service added in isolated environment
     * @param string $id
     * @return bool
     */
    public function initialized($id)
    {
        return isset($this->isolatedService[$id]);
    }

    /**
     * Get a parameter in isolated environment
     * @param string $name
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->isolatedParameters[$name];
    }

    /**
     * Is parameter has been added in isolated environment
     * @param string $name
     * @return bool
     */
    public function hasParameter($name)
    {
        return isset($this->isolatedParameters[$name]);
    }

    /**
     * Set a parameter value to isolated environment
     * @param string $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->isolatedParameters[$name] = $value;
    }
}