Better Reflection
=================

Mimics PHP's [reflection API](http://php.net/manual/en/book.reflection.php) but without actually loading the class at
any point. Like magic. Idea credit goes to @ocramius.

Example usage:

```php
<?php

$classLoader = require "vendor/autoload.php";

$reflector = new Reflector($classLoader);

$reflectionClass = $reflector->reflect('Foo\Bar\MyClass');

echo $reflectionClass->getShortName(); // MyClass
echo $reflectionClass->getName(); // Foo\Bar\MyClass
echo $reflectionClass->getNamespaceName(); // Foo\Bar
```
