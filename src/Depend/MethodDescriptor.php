<?php

namespace Depend;

use ReflectionException;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;

class MethodDescriptor
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $paramNames = array();

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Default constructor
     *
     * @param Manager          $manager
     * @param ReflectionMethod $method
     */
    function __construct(Manager $manager, ReflectionMethod $method)
    {
        $this->name    = $method->getName();
        $this->manager = $manager;

        foreach ($method->getParameters() as $parameter) {
            $name     = $parameter->getName();
            $position = $parameter->getPosition();
            $value    = $this->resolveArgumentValue($parameter);

            $this->params[$name]         = $value;
            $this->paramNames[$position] = $name;
        }
    }

    /**
     * @param ReflectionParameter $param
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function resolveArgumentValue(ReflectionParameter $param)
    {
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        $paramClass = null;

        try {
            $paramClass = $param->getClass();
        }
        catch (ReflectionException $e) {
            // NO-OP
        }

        if (!is_null($paramClass)) {
            return $this->manager->describe($paramClass->getName());
        }

        return null;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get method parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set method parameters
     *
     * @param array $params
     */
    public function setParams(array $params = array())
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }
    }

    /**
     * Set a method parameter by name or index
     *
     * @param string|int $key
     * @param mixed      $value
     *
     * @return $this
     */
    public function setParam($key, $value)
    {
        if (is_numeric($key) && isset($this->paramNames[$key])) {
            $key = $this->paramNames[$key];
        }

        if (!in_array($key, $this->paramNames)) {
            return $this;
        }

        $this->params[$key] = $value;

        return $this;
    }
}
