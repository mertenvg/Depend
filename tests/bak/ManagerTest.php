<?php

namespace Depend;

use ClassA;
use ClassF;
use Depend\Exception\InvalidArgumentException;
use Depend\Exception\RuntimeException;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Manager
     */
    protected $manager;

    public function setUp()
    {
        $factory    = new Manager();
        $descriptor = new ClassDescriptor();

        $this->manager = new Manager($factory, $descriptor);
    }

    public function testAdd()
    {
        $descriptor = new ClassDescriptor();

        $descriptor->setManager($this->manager);

        $descriptor->load(new \ReflectionClass('ClassF'));

        $this->manager->add($descriptor);

        $this->assertEquals($descriptor, $this->manager->describe('ClassF'));
    }

    public function testAlias()
    {
        $descriptor = $this->manager->alias(
            'testF',
            $this->manager->describe('ClassF'),
            array(
                0 => 'testF param1',
            )
        );

        $this->assertInstanceOf('\Depend\Descriptor', $descriptor);

        $this->assertEquals($descriptor, $this->manager->describe('testF'));
    }



    public function testComplexStructure()
    {
        $dm = $this->manager;
        $if = new InjectorFactory();

        $dm->implement('InterfaceOne', 'ClassOne');

        $dm->describe(
            'ClassA',
            array(
                'name'   => 'test',
                'array'  => array(1, 2, 3),
                'except' => null,
            ),
            array(
                $if->create('setB', $dm->describe('ClassB'))
            )
        );

        $dm->describe('ClassB')->setActions(
            array(
                $if->create('setA', $dm->describe('ClassA'))
            )
        );

        $dm->describe('ClassC')->setActions(
            array(
                $if->create('setClassD', $dm->describe('ClassD')),
                $if->create('setClassE', $dm->describe('ClassE')),
                $if->create('setClassOne', $dm->describe('ClassOne')),
                $if->create('setClassXA', $dm->describe('ClassXA')),
            )
        );

        $dm->describe('ClassE')->setActions(
            array(
                $if->create('setInterfaceOne', $dm->describe('InterfaceOne')),
            )
        );

        /** @var $a ClassA */
        $a = $dm->get('ClassA');

        $this->assertInstanceOf('ClassA', $a);
        $this->assertEquals('test', $a->getName());
        $this->assertEquals(array(1, 2, 3), $a->getArray());
        $this->assertInstanceOf('ClassB', $a->getB());
        $this->assertInstanceOf('ClassC', $a->getC());
        $this->assertInstanceOf('ClassD', $a->getD());

    }

    public function testConstructor()
    {
        $dm = new Manager();

        $this->assertEquals($dm, $dm->get('\Depend\Manager'));
    }

    public function testDescribe()
    {
        $descriptor = $this->manager->describe('ClassF');

        $this->assertInstanceOf('\Depend\Descriptor', $descriptor);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testDescribeInterface()
    {
        $this->manager->describe('InterfaceOne');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDescribeNonExistent()
    {
        $this->manager->describe('NonExistentClass');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCircularReferenceDetection()
    {
        $this->manager->describe('ClassCircularRefA');

        $this->manager->get('ClassCircularRefA');
    }

    public function testNonValidActionGracefulFailure()
    {
        $this->manager->describe('ClassC', null, array(
            new CreationAction(),
            'fake action',
        ));

        $c = $this->manager->get('ClassC');

        $this->assertInstanceOf('ClassC', $c);
    }

    public function testGet()
    {
        $f = $this->manager->get('ClassF');

        $this->assertInstanceOf('ClassF', $f);
    }

    public function testGetNonShared()
    {
        $f1 = $this->manager->get('ClassF');

        $descriptor = $this->manager->describe('ClassF');

        $descriptor->setIsShared(false);

        $f2 = $this->manager->get('ClassF');

        $this->assertNotEquals(spl_object_hash($f1), spl_object_hash($f2));
    }

    public function testGetNonSharedAndNonCloneable()
    {
        $f1 = $this->manager->get('ClassF');

        $descriptor = $this->manager->describe('ClassF');
        $descriptor->setIsShared(false);
        $descriptor->setIsCloneable(false);

        $f2 = $this->manager->get('ClassF');

        $this->assertNotEquals(spl_object_hash($f1), spl_object_hash($f2));
    }

    public function testGetWithParamsOverride()
    {
        /** @var $f ClassF */
        $f = $this->manager->get('ClassF', array(0 => __METHOD__));

        $this->assertInstanceOf('ClassF', $f);

        $this->assertEquals(__METHOD__, $f->getParam1());
    }

    public function testImplement()
    {
        $this->manager->implement('InterfaceOne', 'ClassOne');

        $one = $this->manager->get('InterfaceOne');

        $this->assertInstanceOf('ClassOne', $one);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testImplementException()
    {
        $this->manager->implement('InterfaceOne', 'ClassF');
    }

    public function testResolveParams()
    {
        $input = array(
            $this->manager->describe('ClassF'),
            'test string',
        );

        $params = $this->manager->resolveParams($input);

        $this->assertInstanceOf('ClassF', $params[0]);
        $this->assertEquals($input[1], $params[1]);
    }

    public function testSet()
    {
        $expected = (object) array(
            'param1' => 'string1',
            'param2' => 'string2',
            'param3' => 'string3',
        );

        $this->manager->set('testStdClass', $expected);

        $this->assertEquals(spl_object_hash($expected), spl_object_hash($this->manager->get('testStdClass')));
    }
}
