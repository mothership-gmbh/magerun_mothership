<?php

namespace Mothership\Tests;

/**
 * PHP Version 5.4
 *
 * Class ${NAME}
 *
 * @category  Mothership
 * @package   Mothership_${NAME}
 * @author    Don Bosco van Hoi <vanhoi@mothership.de>
 * @copyright 2015 Mothership GmbH
 * @link      http://www.mothership.de/
 */
trait TraitBase {

    /**
     * call private methods
     *
     * @param object &$object Object
     * @param string $methodName methods
     * @param array $parameters params
     * @return mixed Method return.
     */
    protected function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Get private property value
     *
     * @param string $object
     * @param string $propertyName
     *
     * @return mixed
     */
    protected function getPropertyValue(&$object, $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    /**
     * @param $object
     * @param $propertyName
     * @return string
     */
    protected function getPropertyClass(&$object, $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return get_class($property->getValue($object));
    }
}