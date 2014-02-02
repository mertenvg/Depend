<?php

namespace Depend;

use Depend\Exception\RuntimeException;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Manager
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new Manager();
    }

    public function testCreate()
    {
        $manager = new Manager();
        $descriptor = $manager->describe('ClassStub');

        $instance = $this->factory->create($descriptor, $manager);

        $this->assertInstanceOf('ClassStub', $instance);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCreateNonInstantiable()
    {
        $manager = new Manager();
        $descriptor = $manager->describe('ClassNoInstance');

        $this->factory->create($descriptor, $manager);
    }
}
