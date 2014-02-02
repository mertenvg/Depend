<?php

namespace Depend;

class DescriptorCache
{
    /**
     * @var ClassDescriptor[]
     */
    protected $descriptors = array();

    /**
     * Get a reference to the descriptor. Remember to use & when assigning
     * the return value to a variable so the reference is maintained.
     *
     * @param string $name
     *
     * @return ClassDescriptor
     */
    public function & get($name)
    {
        if (!isset($this->descriptors[$name])) {
            $this->set($name);
        }

        return $this->descriptors[$name];
    }

    /**
     * Store a descriptor or set a placeholder for one.
     *
     * @param string          $name
     * @param ClassDescriptor $descriptor
     *
     * @return $this
     */
    public function set($name, ClassDescriptor $descriptor = null)
    {
        $this->descriptors[$name] = $descriptor;

        return $this;
    }

    /**
     * Check for existence of a descriptor by name
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->descriptors[$name]);
    }
}
