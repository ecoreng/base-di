<?php

namespace Base\Test;

class ResolverTest extends \PHPUnit_Framework_TestCase
{

    protected $resolver;
    protected $testObject = 'Base\Test\Objects\TestObject';

    public function setUp()
    {
        $container = new \Base\Concrete\Container;
        $this->resolver = new \Base\Concrete\Di\Resolver($container);
    }

    public function testGetReflectionClass()
    {

        $r = $this->resolver->getReflectionClass($this->testObject);
        $r2 = $this->resolver->getReflectionClass($this->testObject);
        $this->assertInstanceOf('\Reflector', $r);
        $this->assertEquals($this->testObject, $r->getName());
        $this->assertSame($r, $r2);
    }

    public function testGetReflectionMethod()
    {
        $to = new Objects\TestObjectSetters;
        $r = $this->resolver->getReflectionMethod($to, 'setId2');
        $r2 = $this->resolver->getReflectionMethod($to, 'setId2');
        $this->assertInstanceOf('\Reflector', $r);
        $this->assertEquals('setId2', $r->getName());
        $this->assertSame($r, $r2);
    }

    public function testGetConstructorArgs()
    {
        $to = 'Base\Test\Objects\TestObjectDependencies';
        $toEntry = new \Base\Concrete\Di\Definition([
            'alias' => $to,
            'implementation' => $to,
        ]);
        $ctorArgs = $this->resolver->getConstructorArgs($toEntry);
        $this->assertEquals(true, array_key_exists('foo', $ctorArgs));
        $this->assertEquals(true, array_key_exists('argClass', $ctorArgs['foo']));
        $this->assertEquals('Base\Test\Objects\TestObject', $ctorArgs['foo']['argClass']);
        
    }
    
    public function testGetConstructorArgsDefault()
    {
        $to = 'Base\Test\Objects\TestObjectDependencyDefault';
        $toEntry = new \Base\Concrete\Di\Definition([
            'alias' => $to,
            'implementation' => $to,
        ]);
        $ctorArgs = $this->resolver->getConstructorArgs($toEntry);
        $this->assertEquals(true, array_key_exists('foo2', $ctorArgs));
        $this->assertEquals(true, array_key_exists('argDefault', $ctorArgs['foo2']));
        $this->assertEquals(null, $ctorArgs['foo2']['argDefault']);
    }
    
    public function testGetMethodArgs()
    {
        $to = new Objects\TestObjectSetters;
        $strArgs = $this->resolver->getMethodArgs('foo', $to, 'setId2');
        $this->assertEquals(true, array_key_exists('id2', $strArgs));
    }

}
