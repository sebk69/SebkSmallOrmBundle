<?php
/**
 * This file is a part of SebkSmallOrmBundle
 * Copyrigtht 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\SmallOrmBundle\Dao;

/**
 * Model base collection
 */
class ModelCollection implements \ArrayAccess
{
    protected $objects;

    /**
     * @param \Sebk\SmallOrmBundle\Dao\ModelCollection || array $array
     * @throws DaoException
     */
    public function __construct($array = array())
    {
        if ($array instanceof ModelCollection) {
            $this->objects = $array->objects;
        } elseif (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($value instanceof Model || $value === null) {
                    $this->objects[$key] = $value;
                } else {
                    throw new DaoException("You can only add Sebk\\SmallOrmBundle\\Dao\\Model objects to ModelCollection");
                }
            }
        } else {
            throw new DaoException("Sebk\\SmallOrmBundle\\Dao\\ModelCollection constructor accept only array and ModelCollection parameter");
        }
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        if (array_key_exists($key, $this->objects)) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param string $key
     * @return Model
     * @throws DaoException
     */
    public function offsetGet($key)
    {
        if (array_key_exists($key, $this->objects)) {
            return $this->objects[$key];
        }

        throw new DaoException("Offset '$key' doesn't exists");
    }

    /**
     *
     * @param string $key
     * @param \Sebk\SmallOrmBundle\Dao\Model $value
     * @throws DaoException
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key) && ($value instanceof Model || $value === null)) {
            $this->objects[] = $value;

            return;
        } elseif ($value instanceof Model || $value === null) {
            $this->objects[$key] = $value;

            return;
        }

        throw new DaoException("You can only add Sebk\\SmallOrmBundle\\Dao\\Model objects to ModelCollection");
    }

    /**
     * @param string $key
     * @throws DaoException
     */
    public function offsetUnset($key)
    {
        if (array_key_exists($key, $this->objects)) {
            unset($this->objects[$key]);
        }

        throw new DaoException("Offset '$key' doesn't exists");
    }
}