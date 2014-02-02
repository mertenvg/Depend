<?php

namespace Depend;

use ReflectionClass;

class ClassDescriptor
{
    /**
     * @var ClassDescriptor
     */
    protected $parent;

    /**
     * @var ClassDescriptor[]
     */
    protected $interfaces;

    /**
     * @var array
     */
    protected $actions = array();

    /**
     * @var boolean
     */
    protected $cloneable = false;

    /**
     * @var boolean
     */
    protected $shared = true;

    /**
     * @var MethodDescriptor
     */
    protected $constructor;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var string
     */
    protected $name;

    /**
     * Default constructor
     *
     * @param Manager          $manager
     * @param ReflectionClass  $class
     * @param MethodDescriptor $constructor
     */
    function __construct(Manager $manager, ReflectionClass $class, MethodDescriptor $constructor = null)
    {
        $this->manager     = $manager;
        $this->name        = $class->getName();
        $this->interfaces  = $this->resolveInterfaces($class);
        $this->parent      = $this->resolveParent($class);
        $this->constructor = $constructor;
    }

    /**
     * @param ReflectionClass $class
     *
     * @return ClassDescriptor[]
     */
    protected function resolveInterfaces(ReflectionClass $class)
    {
        $interfaces = array();

        foreach ($class->getInterfaceNames() as $interface) {
            $interfaces[] = $this->manager->describe($interface);
        }

        return $interfaces;
    }

    /**
     * @param ReflectionClass $class
     *
     * @return ClassDescriptor
     */
    protected function resolveParent(ReflectionClass $class)
    {
        $parent = $class->getParentClass();

        if ($parent instanceof ReflectionClass) {
            return $this->manager->describe($parent->getName());
        }

        return null;
    }

    /**
     * @param boolean $cloneable
     *
     * @return ClassDescriptor
     */
    public function setIsCloneable($cloneable)
    {
        $this->cloneable = $cloneable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCloneable()
    {
        $cloneable = $this->cloneable;

        if ($this->parent instanceof ClassDescriptor) {
            $cloneable = $cloneable && $this->parent->isCloneable();
        }

        return $cloneable;
    }

    /**
     * @param boolean $shared
     *
     * @return ClassDescriptor
     */
    public function setIsShared($shared)
    {
        $this->shared = $shared;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShared()
    {
        $shared = $this->shared;

        if ($this->parent instanceof ClassDescriptor) {
            $shared = $shared && $this->parent->isShared();
        }

        return $shared;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        $actions = $this->actions;

        foreach ($this->interfaces as $interface) {
            $actions = array_replace($interface->getActions(), $actions);
        }

        if ($this->parent instanceof ClassDescriptor) {
            $actions = array_replace($this->parent->getActions(), $actions);
        }

        return $actions;
    }

    /**
     * @param array $actions
     *
     * @return $this
     */
    public function setActions(array $actions = array())
    {
        foreach ($actions as $method => $params) {
            $this->setAction($method, $params);
        }

        return $this;
    }

    /**
     * @param string $method
     * @param array  $params
     *
     * @return $this
     */
    public function setAction($method, $params = array())
    {
        if (!is_array($params)) {
            $params = array($params);
        }

        $this->actions[$method] = $this->manager->action($this->getName(), $method, $params);

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        if ($this->constructor instanceof MethodDescriptor) {
            $this->constructor->setParams($params);
        }
        else if ($this->parent instanceof ClassDescriptor) {
            $this->parent->setParams($params);
        }

        return $this;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function setParam($name, $value)
    {
        $this->setParams(array($name => $value));

        return $this;
    }

    /**
     *
     */
    public function getParams()
    {
        if ($this->constructor instanceof MethodDescriptor) {
            return $this->constructor->getParams();
        }
        else if ($this->parent instanceof ClassDescriptor) {
            return $this->parent->getParams();
        }

        return array();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
