<?php

namespace Depend;

class DescriptorCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DescriptorCache
     */
    protected $cache;

    public function setUp()
    {
        parent::setUp();

        $this->cache = new DescriptorCache();
    }

    public function testSetGetMethods()
    {
        $descriptor = new ClassDescriptor('name');

        $this->assertInstanceOf(
            'Manager\DescriptorCache',
            $this->cache->set('aDescriptor', $descriptor)
        );

        $this->assertNull($this->cache->get('aNonExistantDescriptor'));

        $this->assertEquals($descriptor, $this->cache->get('aDescriptor'));
    }

    public function testPlaceholderReference()
    {
        $descriptor = new ClassDescriptor('name');

        $placeholder = & $this->cache->get('aPlaceholder');

        $this->assertNull($placeholder);

        $this->cache->set('aPlaceholder', $descriptor);

        $this->assertNotNull($placeholder);
        $this->assertEquals($descriptor, $placeholder);
    }
}
