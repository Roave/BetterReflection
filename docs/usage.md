# Basic Usage

The starting point for creating a reflection class does not match the typical
core reflection API. Instead of simply instantiating a `new \ReflectionClass`,
you must use the appropriate helper `\BetterReflection\Reflector\ClassReflector`.

All `*Reflector` classes require a class that implements the `SourceLocator`
interface as a dependency.

## SourceLocators

Source locators are helpers that identify how to load code that can be used
within the `Reflector`s. The library comes bundled with the following 
`SourceLocator` classes:

 * `ComposerSourceLocator` - you will probably use this most of the time. This
    uses Composer's built-in autoloader to locate a class and return the source.
    
 * `SingleFileSourceLocator` - this locator simply loads the filename specified
    in the constructor.
    
 * `StringSourceLocator` - pass a string as a constructor argument which will
    be used directly. Note that any references to filenames when using this
    locator will be `null` because no files are loaded.

A `SourceLocator` is a callable, which when invoked must be given an
`Identifier` (which describes a class/function/etc.). The `SourceLocator`
should be written so that it returns a `LocatedSource` object, which describes
source code and the filename in which the source code was loaded.

## Reflecting Classes

The `ClassReflector` is used to create Better Reflection `ReflectionClass`
instances. You may pass it any `SourceLocator` to reflect on any class that
can be located using the given `SourceLocator`.

### Example usage with the Composer autoloader:

```php
<?php

$classLoader = require "vendor/autoload.php";

use BetterReflection\Reflector\ClassReflector;

$reflector = new ClassReflector(new ComposerSourceLocator($classLoader));
$reflectionClass = $reflector->reflect('Foo\Bar\MyClass');

echo $reflectionClass->getShortName(); // MyClass
echo $reflectionClass->getName(); // Foo\Bar\MyClass
echo $reflectionClass->getNamespaceName(); // Foo\Bar
```

### Example usage for loading a class from a specific file:

```php
<?php

$reflector = new ClassReflector(new SingleFileSourceLocator('path/to/MyApp/MyClass.php'));
$reflectionClass = $reflector->reflect('MyApp\MyClass');

echo $reflectionClass->getShortName(); // MyClass
echo $reflectionClass->getName(); // MyApp\MyClass
echo $reflectionClass->getNamespaceName(); // MyApp
```

### Example usage for loading a class from a string:

```php
<?php

$code = '<?php class Foo {};';

$reflector = new ClassReflector(new StringSourceLocator($code));
$reflectionClass = $reflector->reflect('Foo');

echo $reflectionClass->getShortName(); // Foo
```

### Example usage to fetch a list of classes from a file

```php
<?php

$reflector = new ClassReflector(new SingleFileSourceLocator('path/to/file.php'));
$classes = $reflector->getAllClasses();
```
