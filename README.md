Better Reflection
=================

Mimics PHP's [reflection API](http://php.net/manual/en/book.reflection.php) but without actually loading the class at
any point. Like magic. Idea credit goes to @ocramius.

Example usage with the Composer autoloader:

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

Example usage for loading a class from a specific file:

```php
<?php

$reflector = new ClassReflector(new SingleFileSourceLocator('path/to/MyApp/MyClass.php'));
$reflectionClass = $reflector->reflect('MyApp\MyClass');

echo $reflectionClass->getShortName(); // MyClass
echo $reflectionClass->getName(); // MyApp\MyClass
echo $reflectionClass->getNamespaceName(); // MyApp
```

Example usage for loading a class from a string:

```php
<?php

$code = '<?php class Foo {};';

$reflector = new ClassReflector(new StringSourceLocator($code));
$reflectionClass = $reflector->reflect('Foo');

echo $reflectionClass->getShortName(); // Foo
```

Example usage to fetch a list of classes from a file

```php
<?php

$reflector = new ClassReflector(new SingleFileSourceLocator('path/to/file.php'));
$classes = $reflector->getClassesFromFile();
```
