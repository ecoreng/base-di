Base DI
=======

Basic Dependency Injector for constructor and setter injection, with an emphasis in mapping interfaces to implementations (objects or closures) and resolving dependencies in "callable"s/Closures and cacheable auto dependency resolutions (currently just APC adapter).

requires PHP 5.4

This library implements the "Container Interoperability" interface

Read More:
https://github.com/container-interop/container-interop

Packagist: "container-interop/container-interop"



Inspired by orno/di now thephpleague/container and rdlowrey\auryn

##Basic Usage##

Set and get a dependency
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Object');
$obj = $this->get('Some\Object');
```

Define an interface and an implementation
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Interface', 'Some\Object');
$obj = $this->get('Some\Interface');

// $obj contains a Some\Object instance
```

Requesting the interface returns the same object as if we request the
object directly
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Interface', 'Some\Object');
$obj = $this->get('Some\Interface');

$di->set('Some\Object');
$obj2 = $this->get('Some\Object');

// $obj1 and $obj2 contain the same instance
```

Unless you set them not to:
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Interface', 'Some\Object')->setSingleton(false);;
$obj = $this->get('Some\Interface');

$di->set('Some\Object')->setSingleton(false);
$obj2 = $this->get('Some\Object');

// $obj1 and $obj2 contain different instances
```

Interfaces are the suggested alias for classes, as these will help autoresolve your parameter dependencies to Interfaces (more on this later), but actually anything will work:
```php
$di = new \Base\Concrete\Container;
$di->set('somestring-not-a-class-or-interface', 'Some\Object');
$obj = $this->get('somestring-not-a-class-or-interface');

// $obj contains a Some\Object instance
```

All objects are saved in memory and are returned when requested again
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Object');
$obj1 = $di->get('Some\Object');
$obj2 = $di->get('Some\Object');

// $obj1 references the same object as $obj2
```

If this is unwanted behavior, then pass a ``false`` as 3rd param when defining
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Object', null, false);
$obj1 = $di->get('Some\Object');
$obj2 = $di->get('Some\Object');

// $obj1 and $obj2 reference different instances
```
OR use this syntax
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Object')->setSingleton(false);
$obj1 = $di->get('Some\Object');
$obj2 = $di->get('Some\Object');

// $obj1 and $obj2 reference different instances
```


Definitions can be overwritten:
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Interface', 'Some\Object1');
$di->set('Some\Interface', 'Some\Object2');
$obj = $this->get('Some\Interface');

// $obj will contain an instance of Some\Object2
```

You can delegate the instantiation of the implementation (object) to a Closure for complex instantiation logic
```php
$di = new \Base\Concrete\Container;
$di->set('Some\Object', function () {
	$obj = new \Some\Object;
	$obj->foo ='bar';
	return $obj;
});
$obj = $di->get('Some\Object');

// $obj->foo equals 'bar';
```

If you need to return a Closure when you request an object just wrap it with the method ``raw()``
```php
$di = new \Base\Concrete\Container;
$di->set('Foo\SomeFactory', $di->raw(function () {
	return rand(1, 100);
}));
$someFactory = $di->get('Foo\SomeFactory');
$num1 = $someFactory();
$num2 = $someFactory();

// $num1 and $num2 contain different (hopefully) random numbers
```

Or you can define current instances of objects as implementations:
```php
$di = new \Base\Concrete\Container;

$obj = new \Some\Object;
$obj->foo ='bar';

$di->set('Some\Object', $obj);
$obj = $di->get('Some\Object');

// $obj->foo equals 'bar';
```

Container Definitions can also be reused, just create an object that implements ``Base\ServiceRegisterer``
```php
// ServiceRegisterer.php
class ServiceRegisterer implements \Base\ServiceRegisterer
{
	public function register(Interop\Container\ContainerInterface $di)
  {
		$di->set('Some\Interface', 'Some\Object');
	}
}
====
$di = new \Base\Concrete\Container;
$di->register(new ServiceRegisterer);
$obj = $di->get('Some\Interface');

// $obj contains an instance of Some\Object
```

##Definition Objects##

When you define something in the container, it returns an instance of an object that implements ``Base\Interfaces\DiDefinition`` with your latest definition. This object will allow you to mutate the definition further.

##Setter Injection##
You can achieve Setter Injection By using the following syntax
```php
// SetterObject.php
class SetterObject
{
	public $arg1;

	public $arg2;

	public function setterOne($arg1)
	{
		$this->arg1 = $arg1;
	}

	public function setterTwo($arg2)
	{
		$this->arg2 = $arg2;
	}
}
====
$di = new \Base\Concrete\Container;
$di->set('SetterObject')
	->withSetter('setterOne', ['arg1' => 'foo'])
	->withSetter('setterTwo', ['arg2' => 'bar']);

$obj = $di->get('SetterObject');

// $obj->arg1 equals 'foo'
// $obj->arg2 equals 'bar'
```

or instead of the chained call, define a bunch at a time
```php
...

$di = new \Base\Concrete\Container;
$di->set('SetterObject')
	->withSetters(
		[
			'setterOne' => ['arg1' => 'foo'],
			'setterTwo' => ['arg2' => 'bar'],
		]
	);

$obj = $di->get('SetterObject');

// $obj->arg1 equals 'foo'
// $obj->arg2 equals 'bar'
```

##Container Interoperability##
This library implements the "Container Interoperability" interface so the following applies:

You can check the existence of a definition like this:

```php
$di = new \Base\Concrete\Container;
$di->set('Some\Object');
$has1 = $di->has('Some\Object2');
$has2 = $di->has('Some\Object');

// $has1 equals false
// $has2 equals true
```

If a non-existing definition is requested, ``Base\Exception\DependencyNotFoundException`` (which extends ``Interop\Container\Exception\NotFoundException``) is thrown 
```php
$di = new \Base\Concrete\Container;
$obj = $di->get('Some\Object2');

// Base\Exception\DependencyNotFoundException is thrown
```

You can define a delegate lookup container, which will be used to get dependencies instead of the current container, as long as it implements the Container Interoperability interface

```php
$di = new \Base\Concrete\Container;

$newDi = new Awesome\Container;
$newDi->defineObjectOrSomething('Some\Namespace\App');

$di->setDelegateLookupContainer($newDi);

$has = $di->has('Some\Namespace\App');
$app = $di->get('Some\Namespace\App');

// $has equals true
// $app contains whatever the $newDi container resolves 
// (Hopefully an instance of Some\Namespace\App)
```

##Dependency Autoresolution##
The container will always try to resolve your dependencies, even if you dont define them:
```php
// Assuming that \Some\Object exists and is autoloadable
$di = new \Base\Concrete\Container;
$obj1 = $di->get('\Some\Object');

// $obj1 will contain an instance of \Some\Object
```

The container tries to autoresolve all dependencies that your object has as long as they're typehinted, exist and are autoloadable. This is done through Reflections, and the resulting relevant reflection information saved in memory (or cached, see below)
```php
//Dependency1.php

class Dependency1
{

	public $dep2;

	public function __construct(Dependency2 $dep2)
	{
		$this->dep2 = $dep2;
	}

	// ...
}
====
//Dependency2.php

class Dependency2
{
	public $foo = 'bar';

	// ...
}
====

$di = new \Base\Concrete\Container;
$obj1 = $di->get('Dependency1');

// $obj1->dep2->foo equals 'bar'
```

If a default value for a parameter exists, that value will be used, unless you pass parameters to the definition (notice that we dont need to define objects if they exist, and are autoloadable)
```php
//Dependency1.php

class Dependency1
{

	public $dep2;

	public function __construct(Dependency2 $dep2 = null)
	{
		$this->dep2 = $dep2;
	}

	// ...
}
====
//Dependency2.php

class Dependency2
{
	public $foo = 'bar';

	// ...
}
====
// Nothing is passed, so the default value is used
$di = new \Base\Concrete\Container;
$obj1 = $di->get('Dependency1');
// $obj1->dep2 equals null

// Pass parameters when defining the object 
$dep2 = new Depencency2;
$dep2->foo = 'bar';
$di->set('Dependency1', null, ['dep2' => $dep2]);
$obj1 = $di->get('Dependency1');
// $obj1->dep2->foo equals 'bar'

// Or pass parameters when defining the object with a chained method call
$dep2 = new Depencency2;
$dep2->foo = 'bar';
$di->set('Dependency1')->withArgument('dep2', $dep2);

// Or pass multiple parameters when defining the object with a chained method call
$dep2 = new Depencency2;
$dep2->foo = 'bar';
$di->set('Dependency1')->withArguments(['dep2' => $dep2]);


// OR pass parameters when requesting the object 
// (notice that we need to call 'setArgs' first)
$dep2 = new Depencency2;
$dep2->foo = 'bar';
$obj1 = $di->setArgs(['dep2' => $dep2])->get('Dependency1');
// $obj1->dep2->foo equals 'bar'

// OR pass parameters when requesting the object as a reference
// to another object in the container (notice that we need to call 'setArgs' first)
$obj1 = $di->setArgs(['dep2' => '@Dependency2'])->get('Dependency1');
// $obj1->dep2->foo equals 'bar'

```

##Caching Reflection Data###

By default, the container saves in memory the data it gets from Reflections, but it's also possible to cache this information by passing a preset ``Base\Interfaces\DiResolver`` object with a caching ``Base\Interfaces\DiPoolStorage`` object, currently an APC pool storage is available.
```php
$resolver = new \Base\Concrete\Di\Resolver(new \Base\Concrete\Di\ApcPoolStorage);
$di = new Base\Concrete\Container($resolver);
...
// regular usage
```

Creating your own cache driver is as simple as implementing the ``Base\Interfaces\DiPoolStorage`` interface:
```php
namespace Base\Interfaces;

interface DiPoolStorage
{
    public function get($unique, $type);
    public function set($unique, $type, $value);
}

```

##Black Magic##
Callables can also be autoresolved and returned as a Closure for later execution:
```php
// assuming \Some\Object and \Some\Object2 exist and are autoloadable
$di = new \Base\Concrete\Container;

$MyClosure = function ($id, \Some\Object $obj1 = null, \Some\Object2 $obj2) {
	return [$id, $obj1, $obj2];
};
$reference = 'ResolvedClosure-134'

$exec = $di->getExecutableFromCallable($reference, $MyClosure, [2]);
$returnArray = $exec();

// $returnArray contains an array:
// first element equals 2
// second element contains an instance of \Some\Object1 (autoresolved)
// third element contains an instance of \Some\Object2 (autoresolved)
```

Or you can pass an associative array where the order of the params doesn't matter
```php
// assuming \Some\Object and \Some\Object2 exist and are autoloadable
$di = new \Base\Concrete\Container;

$MyClosure = function (\Some\Object $obj1, $id, \Some\Object2 $obj2) {
	return [$id, $obj1, $obj2];
};
$reference = 'ResolvedClosure-134'

$exec = $di->getExecutableFromCallable($reference, $MyClosure, ['id' => 2]);
$returnArray = $exec();

// $returnArray contains an array:
// first element contains an instance of \Some\Object
// second element equals 2
// third element contains an instance of \Some\Object2
```

Manually prepare an instantiated Object as another definition (Manual Setter Injection as Interface). This is a useful feature when you want to free people from unnecesary inheritance but still have a way to prepare an object properly.

```php
// ControllerInterface.php
interface ControllerInterface
{
	public function setView(View $view);
	public function setRequest(Request $request);
}
====
// ControllerTrait.php
trait ControllerTrait
{
	public function setView(View $view){...}
	public function setRequest(Request $request){...}
}
====
// SomeController.php
class SomeController implements ControllerInterface
{
  use ControllerTrait; // or their own implementation
 
	// autoresolvable constructor arguments for this specific class
	public function __construct(Some\Dependency $dep, Some\OtherDependency $dep2)
	{
		...
		// no need to call parent::__construct() with a bunch of objects
	}
}
===
// assuming dependencies exist and are autoloadable

$di = new \Base\Concrete\Container;
$di->set('ControllerInterface')
	->withSetters(
		[
			'setView', => ['view' => '@View'],
			'setRequest', ['request' => '@Request']
		]
	);

// resolve userland dependencies
$ctrl = $di->get('SomeController');

// check if the interface is implemented
if ($ctrl instanceof 'ControllerInterface') {
	
	// prepare the object further
	$di->setterInjectAs('ControllerInterface', $ctrl);
}

// $ctrl should be ready to be used

```


