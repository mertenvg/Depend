<?php

namespace Depend;

use Depend\Abstraction\ModuleInterface;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

class Manager
{
    /**
     * @var ReflectionClass[]
     */
    protected $reflections = array();

    /**
     * @var DescriptorCache
     */
    protected $descriptors;

    /**
     * @var object[]
     */
    protected $instances = array();

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * Default constructor.
     *
     * @param Factory $factory
     */
    function __construct(Factory $factory = null)
    {
        if (!$factory instanceof Factory) {
            $factory = new Factory;
        }

        $this->factory     = $factory;
        $this->descriptors = $factory->createDescriptorCache();
    }

    /**
     * @param       $name
     * @param array $paramsOverride
     *
     * @throws \RuntimeException
     * @return object
     */
    public function get($name, $paramsOverride = array())
    {
        $key = $this->normalizeName($name);

        $descriptor      = $this->descriptors->get($key);
        $reflectionClass = $this->resolveReflectionClass($name);
        $class           = $reflectionClass->getName();

        if ($descriptor->isShared() && isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if ($descriptor->isCloneable() && isset($this->instances[$key])) {
            return clone $this->instances[$key];
        }

        if (!$reflectionClass->isInstantiable()) {
            throw new RuntimeException("Class '$class' is is not instantiable");
        }

        $this->instances[$key] = null;

        $args = $this->resolveParams(
            array_replace($descriptor->getParams(), $paramsOverride)
        );

        if (empty($args)) {
            $instance = $reflectionClass->newInstance();
        }
        else {
            $instance = $reflectionClass->newInstanceArgs($args);
        }

        $this->instances[$key] = $instance;

        $actions = $descriptor->getActions();

        /** @var $action MethodDescriptor */
        foreach ($actions as $method => $action) {
            if ($action instanceof MethodDescriptor) {
                $method = $action->getName();
                $params = $action->getParams();
            }
            else {
                $params = $action;
            }

            if (method_exists($instance, $method)) {
                call_user_func_array(
                    array($instance, $method),
                    $this->resolveParams($params)
                );
            }
        }

        return $instance;
    }

    /**
     * @param $className
     *
     * @return ReflectionClass
     * @throws \InvalidArgumentException
     */
    protected function resolveReflectionClass($className)
    {
        if (!class_exists($className) && !interface_exists($className)) {
            throw new InvalidArgumentException("Class '$className' could not be found");
        }

        $key = $this->normalizeName($className);

        if (isset($this->reflections[$key])) {
            return $this->reflections[$key];
        }

        $this->reflections[$key] = $this->factory->createReflectionClass($className);

        return $this->reflections[$key];
    }

    /**
     * Register an implementation of the given interface
     *
     * @param string $interface
     * @param string $className
     * @param array  $actions
     *
     * @return ClassDescriptor
     * @throws \InvalidArgumentException
     */
    public function implement($interface, $className, array $actions = array())
    {
        if (!$this->resolveReflectionClass($className)->implementsInterface($interface)) {
            throw new InvalidArgumentException("Given class '$className' does not implement '$interface'");
        }

        $descriptor = $this->describe($interface, array(), $actions, $className);

        $this->descriptors->set(
            $this->normalizeName($interface),
            $descriptor
        );

        return $descriptor;
    }

    /**
     * @param        $name
     * @param array  $params
     * @param array  $actions
     * @param null   $implementation
     *
     * @return ClassDescriptor
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function describe($name, array $params = array(), array $actions = array(), $implementation = null)
    {
        $key = $this->normalizeName($name);

        if (!empty($implementation)) {
            $descriptor = $this->describe($implementation, $params, $actions);

            $this->descriptors->set($key, $descriptor);

            return $descriptor;
        }

        if ($this->descriptors->has($key)) {
            /** @var $descriptor ClassDescriptor */
            $descriptor = $this->descriptors->get($key);
            $descriptor->setParams($params);

            if (!is_null($actions)) {
                $descriptor->setActions($actions);
            }

            return $descriptor;
        }

        $reflectionClass = $this->resolveReflectionClass($name);
        $constructor     = $reflectionClass->getConstructor();
        $descriptor      = $this->factory->createClassDescriptor(
            $this,
            $reflectionClass,
            !is_null($constructor) ? $this->factory->createMethodDescriptor($this, $constructor) : null
        );
        $descriptor->setActions($actions);
        $descriptor->setParams($params);

        $this->descriptors->set($key, $descriptor);

        return $descriptor;
    }

    /**
     * Register an implementation of ModuleInterface
     *
     * @param string $className
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function module($className)
    {
        if (!class_exists((string) $className)) {
            throw new InvalidArgumentException("Module class '$className' could not be found");
        }

        $module = new $className;

        if (!($module instanceof ModuleInterface)) {
            throw new InvalidArgumentException("Module class '$className' mustimplement 'Manager\\Abstraction\\ModuleInterface'");
        }

        $module->register($this);

        return $this;
    }

    /**
     * Create an action descriptor
     *
     * @param string $className
     * @param string $methodName
     * @param array  $params
     *
     * @return MethodDescriptor
     */
    public function action($className, $methodName, array $params = array())
    {
        $reflectionMethod = $this->factory->createReflectionMethod($className, $methodName);
        $method           = $this->factory->createMethodDescriptor($this, $reflectionMethod);

        $method->setParams($params);

        return $method;
    }

    /**
     * Normalize a class name to reduce possibility of conflicts
     * and mismatches due to case insensitivity.
     *
     * @param string $className
     *
     * @return string
     */
    protected function normalizeName($className)
    {
        return trim(strtolower($className), '\\');
    }

    /**
     * Resolve an array of mixed parameters and possible Descriptors.
     *
     * @param array $params
     *
     * @return array
     */
    protected function resolveParams(array $params)
    {
        if (empty($params)) {
            return $params;
        }

        foreach ($params as $key => $param) {
            if (!$param instanceof ClassDescriptor) {
                continue;
            }

            $params[$key] = $this->get($param->getName());
        }

        return $params;
    }
}
