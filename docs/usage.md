# Basic Usage

The starting point for creating a reflection class does not match the typical core reflection API. Instead of 
instantiating a `new \ReflectionClass`, you must use the appropriate `\Roave\BetterReflection\Reflector\ClassReflector` 
helper.

All `*Reflector` classes require a class that implements the `SourceLocator` interface as a dependency.

## Basic Reflection

Better Reflection is, in most cases, able to automatically reflect on classes by using a similar creation technique to 
PHP's internal reflection. However, this works on the basic assumption that whichever autoloader you are using will
attempt to load a file, and only one file, which should contain the class you are trying to reflect. For example, the 
autoloader that Composer provides will work with this technique.

```php
<?php

use Roave\BetterReflection\BetterReflection;

$classInfo = (new BetterReflection)
    ->classReflector()
    ->reflect(\Foo\Bar\MyClass::class);
```

If this instantiation technique is not possible - for example, your autoloader does not load classes from file, then 
you *must* use `SourceLocator` creation.

> Fun fact... using the method described above actually uses a SourceLocator under the hood - it uses the 
  `AutoloadSourceLocator`.

### Initialisers

There are several static initialisers you may use based on the same concept. They are as follows:

```php
<?php

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Reflection\ReflectionProperty;

ReflectionClass::createFromName(\stdClass::class);
ReflectionClass::createFromInstance(new \stdClass);

ReflectionMethod::createFromName(\SplDoublyLinkedList::class, 'add');
ReflectionMethod::createFromInstance(new \SplDoublyLinkedList, 'add');

ReflectionParameter::createFromClassNameAndMethod(\SplDoublyLinkedList::class, 'add', 'index');
ReflectionParameter::createFromClassInstanceAndMethod(new \SplDoublyLinkedList, 'add', 'index');
ReflectionParameter::createFromSpec([\SplDoublyLinkedList::class, 'add'], 'index');
ReflectionParameter::createFromSpec([new \SplDoublyLinkedList, 'add'], 'index');
ReflectionParameter::createFromSpec('my_function', 'param1');
// Creating a ReflectionParameter from a closure is not supported yet :(

ReflectionProperty::createFromName(\ReflectionFunctionAbstract::class, 'name');
ReflectionProperty::createFromInstance(new \ReflectionClass(\stdClass::class), 'name');
```

## SourceLocators

Source locators are helpers that identify how to load code that can be used within the `Reflector`s. The library comes 
bundled with the following `SourceLocator` classes:

 * `ComposerSourceLocator` - you'll probably use this most of the time. This uses Composer's built-in autoloader to 
   locate a class and return the source.
    
 * `SingleFileSourceLocator` - this locator loads the filename specified in the constructor.
    
 * `StringSourceLocator` - pass a string as a constructor argument which will be used directly. Note that any 
   references to filenames when using this locator will be `null` because no files are loaded.

 * `AutoloadSourceLocator` - this is a little hacky, but works on the assumption that when a registered autoloader 
  identifies a file and attempts to open it, then that file will contain the class. Internally, it works by overriding
  the `file://` protocol stream wrapper to grab the path of the file the autoloader is trying to locate. This source 
  locator is used internally by the `ReflectionClass::createFromName` static constructor.

 * `EvaledCodeSourceLocator` - used to perform reflection on code that is already loaded into memory using `eval()`

 * `PhpInternalSourceLocator` - used to perform reflection on PHP's internal classes and functions.

 * `AnonymousClassObjectSourceLocator` - used to perform reflection on an anonymous class object.

 * `ClosureSourceLocator` - used to perform reflection on a closure.

 * `AggregateSourceLocator` - a combination of multiple `SourceLocator`s which are hunted through in the given order to 
   locate the source.

 * `FileIteratorSourceLocator` - iterates all files in a given iterator containing `SplFileInfo` instances.

 * `DirectoriesSourceLocator` - iterates over all `.php` files in a list of directories, and all their descendants.

A `SourceLocator` is a callable, which when invoked must be given an `Identifier` (which describes a class/function/etc)
. The `SourceLocator` should be written so that it returns a `Reflection` object directly.

> Note that using `EvaledCodeSourceLocator` and `PhpInternalSourceLocator` will result in specific types of 
  `LocatedSource` within the reflection - namely `EvaledLocatedSource` and `InternalLocatedSource` respectively.

## Reflecting Classes

The `ClassReflector` is used to create Better Reflection `ReflectionClass` instances. You may pass it any 
`SourceLocator` to reflect on any class that can be located using the given that `SourceLocator`.

### Using the AutoloadSourceLocator

There is no need to use the `AutoloadSourceLocator` directly. Use the static constructors for `ReflectionClass` 
and `ReflectionFunction`:

```php
<?php

$classInfo = ReflectionClass::createFromName('MyClass');
$functionInfo = ReflectionFunction::createFromName('foo');
```

### Using the Composer autoloader directly

```php
<?php

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;

$classLoader = require 'vendor/autoload.php';

$astLocator = (new BetterReflection())->astLocator();
$reflector = new ClassReflector(new ComposerSourceLocator($classLoader, $astLocator));
$reflectionClass = $reflector->reflect('Foo\Bar\MyClass');

echo $reflectionClass->getShortName(); // MyClass
echo $reflectionClass->getName(); // Foo\Bar\MyClass
echo $reflectionClass->getNamespaceName(); // Foo\Bar
```

### Loading a class from a specific file

```php
<?php

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

$astLocator = (new BetterReflection())->astLocator();
$reflector = new ClassReflector(new SingleFileSourceLocator('path/to/MyApp/MyClass.php', $astLocator));
$reflectionClass = $reflector->reflect('MyApp\MyClass');

echo $reflectionClass->getShortName(); // MyClass
echo $reflectionClass->getName(); // MyApp\MyClass
echo $reflectionClass->getNamespaceName(); // MyApp
```

### Loading a class from a string

```php
<?php

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

$code = '<?php class Foo {};';

$astLocator = (new BetterReflection())->astLocator();
$reflector = new ClassReflector(new StringSourceLocator($code, $astLocator));
$reflectionClass = $reflector->reflect('Foo');

echo $reflectionClass->getShortName(); // Foo
```

### Fetch reflections of all the classes in a file

```php
<?php

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

$astLocator = (new BetterReflection())->astLocator();
$reflector = new ClassReflector(new SingleFileSourceLocator('path/to/file.php', $astLocator));
$classes = $reflector->getAllClasses();
```

### Fetch reflections of all the classes in one or more directories

```php
<?php

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;

$astLocator = (new BetterReflection())->astLocator();
$directoriesSourceLocator = new DirectoriesSourceLocator(['path/to/directory1'], $astLocator);
$reflector = new ClassReflector($directoriesSourceLocator);
$classes = $reflector->getAllClasses();
```


## Reflecting Functions

The `FunctionReflector` is used to create Better Reflection `ReflectionFunction` instances. You may pass it any 
`SourceLocator` to reflect on any class that can be located using the given `SourceLocator`.

### Using the AutoloadSourceLocator

See example in "Reflecting Classes" section on the same subheading.

### Fetch reflections of all the functions

```php
<?php

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;

$configuration = new BetterReflection();
$astLocator = $configuration->astLocator();
$classReflector = $configuration->classReflector();

$directoriesSourceLocator = new DirectoriesSourceLocator(['path/to/directory1'], $astLocator);
$reflector = new FunctionReflector($directoriesSourceLocator, $classReflector);
$functions = $reflector->getAllFunctions();
```

### Reflecting a Closure

The `ReflectionFunction` class has a static constructor which you can reflectdirectly on a closure:

```php
<?php

$myClosure = function () {
    echo "Hello world!\n";
};

$functionInfo = ReflectionFunction::createFromClosure($myClosure);
```

> Note that when you reflect on a closure, in order to match the core reflection API, the function "short" name will be 
  just `{closure}`.
