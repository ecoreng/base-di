<?php

namespace Base\Test;

class ContainerTest extends \PHPUnit_Framework_TestCase
{

    protected $di;
    protected $testObject = 'Base\Test\Objects\TestObject';
    protected $testObjectDependencies = 'Base\Test\Objects\TestObjectDependencies';

    public function setUp()
    {
        $this->di = new \Base\Concrete\Container;
        $this->di->set($this->testObject);
        $this->di->set($this->testObjectDependencies);
    }

    public function testSetGetSingleton()
    {
        $obj1 = $this->di->get($this->testObject);
        $obj2 = $this->di->get($this->testObject);
        $this->assertSame($obj1, $obj2);
        $this->assertEquals(false, is_null($obj1));
    }

    public function testSetGetUnique()
    {
        $this->di->set($this->testObject, null, [], false);
        $obj1 = $this->di->get($this->testObject);
        $obj2 = $this->di->get($this->testObject);
        $this->assertNotSame($obj1, $obj2);
        $this->assertEquals(false, is_null($obj1));
    }

    public function testClosureDelegation()
    {
        $this->di->set($this->testObject, function () {
            return new \stdClass();
        });
        $obj1 = $this->di->get($this->testObject);
        $obj2 = $this->di->get($this->testObject);
        $this->assertInstanceOf('stdClass', $obj1);
        $this->assertSame($obj1, $obj2);
        $this->assertEquals(false, is_null($obj1));
    }

    public function testReturnRawClosure()
    {
        $this->di->set($this->testObject, $this->di->raw(function () {
                    return new \stdClass();
                }));
        $obj1 = $this->di->get($this->testObject);
        $obj2 = $this->di->get($this->testObject);
        $this->assertInstanceOf('stdClass', $obj1());
        $this->assertNotSame($obj1(), $obj2());
        $this->assertEquals(false, is_null($obj1()));
    }

    public function testHas()
    {
        $this->assertEquals(true, $this->di->has($this->testObject));
        $this->assertEquals(false, $this->di->has('foo'));
    }

    public function testUndefinedGet()
    {
        $this->setExpectedException('Base\Exception\DependencyNotFoundException');
        $this->di->get('Foo');
    }

    public function testGetDefinition()
    {
        $definition = $this->di->getDefinition($this->testObject);
        $this->assertInstanceOf('Base\Interfaces\DiDefinition', $definition);
    }

    public function testSetGetWithDependencies()
    {
        $obj1 = $this->di->get($this->testObjectDependencies);
        $this->assertInstanceOf('\Base\Test\Objects\TestObjectDependencies', $obj1);
        $this->assertInstanceOf('\Base\Test\Objects\TestObject', $obj1->foo);
    }

    public function testSameWithDependencies()
    {
        $obj1 = $this->di->get($this->testObjectDependencies);
        $obj2 = $this->di->get($this->testObjectDependencies);
        $this->assertInstanceOf('\Base\Test\Objects\TestObjectDependencies', $obj1);
        $this->assertSame($obj1, $obj2);
        $this->assertSame($obj1->foo, $obj2->foo);
    }

    public function testNonDefinedExistingObject()
    {
        $obj1 = $this->di->get('\Base\Test\Objects\TestNonDefinedObject');
        $this->assertInstanceOf('\Base\Test\Objects\TestNonDefinedObject', $obj1);
    }

    public function testNonDefinedExistingObjectWithDependencies()
    {
        $obj1 = $this->di->get('\Base\Test\Objects\TestNonDefinedObjectDependencies');
        $this->assertInstanceOf('\Base\Test\Objects\TestNonDefinedObjectDependencies', $obj1);
        $this->assertInstanceOf('\Base\Test\Objects\TestObject', $obj1->foo);
    }

    public function testPreSetObjectAsInstance()
    {
        // get class names into variables
        $to = $this->testObject;
        $tod = $this->testObjectDependencies;

        // instantiate dependency manually
        $dep = new $to;

        // mutate it
        $dep->id = 2;

        // init object with its dependency injected
        $obj1 = new $tod($dep);

        // set it in container
        $this->di->set($tod, $obj1);

        // get it from container
        $obj2 = $this->di->get($tod);

        // compare 
        $this->assertSame($obj1, $obj2);

        // check dependency mutation
        $this->assertEquals(2, $obj1->foo->id);
    }

    public function testObjectAsParam()
    {
        // get class names into variables
        $to = $this->testObject;
        $tod = $this->testObjectDependencies;

        // instantiate dependency manually
        $dep = new $to;

        // mutate it
        $dep->id = 2;

        $this->di->set($tod)
                ->withArgument('foo', $dep);

        // get it from container
        $obj1 = $this->di->get($tod);

        // compare 
        $this->assertInstanceOf($tod, $obj1);

        // check dependency mutation
        $this->assertEquals(2, $obj1->foo->id);
    }

    public function testServiceAsParam()
    {
        // get class names into variables
        $to = $this->testObject;
        $tod = $this->testObjectDependencies;

        $toInstance = new $to;
        $toInstance->id = 3;
        $this->di->set($to, $toInstance);
        $this->di->set($tod, null, ['foo' => '@' . $to]);
        $this->di->set($tod)
                ->withArgument('foo', '@' . $to);


        // get it from container
        $obj1 = $this->di->get($tod);

        // compare 
        $this->assertSame($toInstance, $obj1->foo);

        // check dependency mutation
        $this->assertEquals(3, $obj1->foo->id);
    }

    public function testUseDefaultValueDependency()
    {
        $tod = 'Base\Test\Objects\TestObjectDependencyDefault';

        $obj1 = $this->di->get($tod);
        $this->assertEquals(null, $obj1->foo2);
    }

    public function testDefinedImplementationReturnsSameInstanceAsNormalDefinition()
    {
        $this->di->set('Some\Interface', $this->testObject);
        $obj1 = $this->di->get('Some\Interface');
        $obj2 = $this->di->get($this->testObject);
        $this->assertSame($obj1, $obj2);
    }

    public function testDefinedImplementationReturnsDifferentInstanceAsNormalDefinitionIfSetToDoSo()
    {

        $this->di->set('Some\Interface', $this->testObject)
            ->setSingleton(false);
        
        $this->di->set($this->testObject)
            ->setSingleton(false);
        
        $obj1 = $this->di->get('Some\Interface');
        $obj2 = $this->di->get($this->testObject);
        $this->assertNotSame($obj1, $obj2);
    }
    
    public function testSetters()
    {
        $to = 'Base\Test\Objects\TestObjectSetters';
        $this->di->set('Some\Interface', $to)
            ->withSetter('setId', ['id' => 10])
            ->withSetter('setId2', ['id2' => 20]);
        $obj = $this->di->get('Some\Interface');
        $this->assertInstanceOf($to, $obj);
        $this->assertEquals(10, $obj->getId());
        $this->assertEquals(20, $obj->getId2());
    }
    
    public function testSettersUnorderedParams()
    {
        $to = 'Base\Test\Objects\TestObjectSetters';
        $this->di->set('Some\Interface', $to)
            ->withSetter('setIds', ['id' => 16, 'id2' => 15]);
        $obj = $this->di->get('Some\Interface');
        $this->assertInstanceOf($to, $obj);
        $this->assertEquals(16, $obj->getId());
        $this->assertEquals(15, $obj->getId2());
    }
}