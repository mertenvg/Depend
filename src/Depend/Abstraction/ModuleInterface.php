<?php

namespace Depend\Abstraction;

use Depend\Manager;

interface ModuleInterface
{
    /**
     * Register the modules classes and interfaces with Manager

     *
*@param Manager $depend

     *
*@return void
     */
    public function register(Manager $depend);
}
