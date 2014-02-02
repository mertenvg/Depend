<?php

namespace Depend;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

class Factory
{
    /**
     * @param string $className
     *
     * @return ReflectionClass
     * @throws RuntimeException
     */
    public function createReflectionClass($className)
    {
        if (!class_exists($className) && !interface_exists($className)) {
            throw new RuntimeException("Unable to create reflection object. Class '$className' could not be found");
        }

        return new ReflectionClass($className);
    }

    /**
     * @param string $className
     * @param string $methodName
     *
     * @return ReflectionMethod
     * @throws \RuntimeException
     */
    public function createReflectionMethod($className, $methodName)
    {
        if (!class_exists($className) && !interface_exists($className)) {
            throw new RuntimeException("Unable to create reflection object. Class '$className' could not be found");
        }

        return new ReflectionMethod($className, $methodName);
    }

    /**
     * Create a class descriptor
     *
     * @param Manager          $manager
     * @param ReflectionClass  $class
     * @param MethodDescriptor $constructor
     *
     * @return ClassDescriptor
     */
    public function createClassDescriptor(
        Manager $manager,
        ReflectionClass $class,
        MethodDescriptor $constructor = null
    ) {
        return new ClassDescriptor($manager, $class, $constructor);
    }

    /**
     * Create a method descriptor
     *
     * @param Manager          $manager
     * @param ReflectionMethod $method
     *
     * @return MethodDescriptor
     */
    public function createMethodDescriptor(Manager $manager, ReflectionMethod $method)
    {
        return new MethodDescriptor($manager, $method);
    }

    /**
     * Create a descriptor cache object
     *
     * @return DescriptorCache
     */
    public function createDescriptorCache()
    {
        return new DescriptorCache();
    }
} 
