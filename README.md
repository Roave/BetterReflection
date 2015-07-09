Better Reflection
=================

[![Build Status](https://travis-ci.org/Roave/BetterReflection.svg?branch=master)](https://travis-ci.org/Roave/BetterReflection) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Roave/BetterReflection/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Roave/BetterReflection/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/Roave/BetterReflection/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Roave/BetterReflection/?branch=master)

Mimics PHP's [reflection API](http://php.net/manual/en/book.reflection.php) but without actually loading the class at
any point. Like magic. Idea credit goes to @ocramius.

## Example usage

```php
$classLoader = require "vendor/autoload.php";

use BetterReflection\Reflector\ClassReflector;

$reflector = new ClassReflector(new ComposerSourceLocator($classLoader));
$reflectionClass = $reflector->reflect('Foo\Bar\MyClass');
```

## More documentation

* [Compatibility with core Reflection API](https://github.com/Roave/BetterReflection/tree/master/docs/compatibility.md)
* [Basic usage instructions](https://github.com/Roave/BetterReflection/tree/master/docs/usage.md)
* [Using types](https://github.com/Roave/BetterReflection/tree/master/docs/types.md)
