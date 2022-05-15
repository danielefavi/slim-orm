<?php

namespace DfTools\SlimOrm\Tests\Lib;

/**
 * Class for making a private method (or attribute) public.
 */
class AccessibilitySwapper
{
    /**
     * The reflection object related to the main object which has private properties 
     * to change into public.
     *
     * @var ReflectionObject
     */
    private $reflectionObj = null;

    /**
     * The object which we are aiming to change some properties from private to
     * public.
     *
     * @var object
     */
    private $object;

    /**
     * Constructor: initialize the reflection object.
     *
     * @param object $object
     */
    public function __construct($object)
    {
        $this->object = $object;

        $this->reflectionObj = new \ReflectionObject($object);
    }

    /**
     * Return the value of a private property.
     *
     * @param string $propertyName
     * @return mixed
     */
    public function getPropertyValue(string $propertyName)
    {
        $property = $this->reflectionObj->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($this->object);
    }

    /**
     * Return the value of a (private) static property.
     *
     * @param string $class
     * @param string $property
     * @return mixed
     */
    public static function getStaticPropertyValue(string $class, string $property)
    {
        $reflection = new \ReflectionClass($class);

        return $reflection->getStaticPropertyValue($property);
    }

    /**
     * Invoke a private method.
     *
     * @param string $methodName
     * @param array ...$args
     * @return void
     */
    public function invokeMethod(string $methodName, ...$args)
    {
        $method = $this->reflectionObj->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invoke($this->object, ...$args);
    }

}