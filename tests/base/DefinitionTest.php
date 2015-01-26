<?php

namespace Base\Test;

class DefinitionTest extends \PHPUnit_Framework_TestCase
{

    protected $definition;

    public function setUp()
    {
        $this->definition = new \Base\Concrete\Di\Definition([
            'alias' => 'foo',
            'arguments' => ['one' => 'yes'],
            'implementation' => 'bar',
            'singleton' => false,
            'setters' => ['moo' => [['cow' => 'one']], 'woof' => [['dog' => 'two']]]
        ]);
    }

    public function testConstructorDefinitionAndGetAlias()
    {
        $def = new \Base\Concrete\Di\Definition([
            'alias' => 'foo2',
        ]);
        $this->assertEquals('foo2', $def->getAlias());
    }

    public function testGetSetSingleton()
    {
        $this->assertEquals(false, $this->definition->isSingleton());
        $this->definition->setSingleton(true);
        $this->assertEquals(true, $this->definition->isSingleton());
    }

    public function testGetImplementation()
    {
        $this->assertEquals('bar', $this->definition->getImplementation());
    }

    public function testGetArguments()
    {
        $args = $this->definition->getArguments();
        $this->assertEquals(true, is_array($args));
        $this->assertEquals(true, array_key_exists('one', $args));
        $this->assertEquals('yes', $args['one']);
    }

    public function testGetArgument()
    {
        $arg = $this->definition->getArgument('one');
        $this->assertEquals('yes', $arg);
    }

    public function testGetSetter()
    {
        $str = $this->definition->getSetter('moo');
        $this->assertEquals(true, is_array($str));
        $this->assertEquals(true, array_key_exists('cow', reset($str)));
        $this->assertEquals('one', $str[0]['cow']);
    }

    public function testGetSetters()
    {
        $strs = $this->definition->getSetters();

        $this->assertEquals(true, is_array($strs));
        $this->assertEquals(true, array_key_exists('moo', $strs));
        $this->assertEquals('one', $strs['moo'][0]['cow']);

        $this->assertEquals(true, array_key_exists('woof', $strs));
        $this->assertEquals('two', $strs['woof'][0]['dog']);
    }

    public function testWithArgument()
    {
        $this->definition->withArgument('something', 'val');
        $arg = $this->definition->getArgument('something');
        $this->assertEquals('val', $arg);
    }

    public function testWithArguments()
    {
        $this->definition->withArguments(['something' => 'val', 'woo' => 'meh']);
        $arg = $this->definition->getArgument('something');
        $this->assertEquals('val', $arg);
        $arg = $this->definition->getArgument('woo');
        $this->assertEquals('meh', $arg);
    }

    public function testWithSetter()
    {
        $this->definition->withSetter('test', ['something' => 'val', 'woo' => 'meh']);
        $strs = $this->definition->getSetters();
        $this->assertEquals(true, is_array($strs));
        $this->assertEquals(3, count($strs));
        $this->assertEquals(true, array_key_exists('test', $strs));
        $this->assertEquals(true, array_key_exists('moo', $strs));
        $this->assertEquals(true, array_key_exists('woof', $strs));
        
        $this->assertEquals(true, array_key_exists('something', reset($strs['test'])));
        $this->assertEquals(true, array_key_exists('woo', reset($strs['test'])));
        $this->assertEquals(true, array_key_exists('cow', reset($strs['moo'])));
        $this->assertEquals(true, array_key_exists('dog', reset($strs['woof'])));
        
        $this->assertEquals('one', $strs['moo'][0]['cow']);
        $this->assertEquals('two', $strs['woof'][0]['dog']);
        
        $this->assertEquals('val', $strs['test'][0]['something']);
        $this->assertEquals('meh', $strs['test'][0]['woo']);
    }
    
    public function testMultiSetters()
    {
        $this->definition->withSetter('test', ['something' => 'val1']);
        $this->definition->withSetter('test', ['something' => 'val2']);
        $strs = $this->definition->getSetters();
        
        $this->assertEquals(2, count($strs['test']));
        $this->assertEquals('val1', $strs['test'][0]['something']);
        $this->assertEquals('val2', $strs['test'][1]['something']);
    }
}
