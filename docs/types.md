# Using Types

When you have a `ReflectionParameter`, you can determine types in the following ways:

```php
class MyClass
{
    /**
     * @param array $myParameter
     */
    public function myMethod(array $myParameter = [])
    {
        // ... stuff ...
    }
}
```

```php
<?php

use Roave\BetterReflection\BetterReflection;

$classInfo     = (new BetterReflection())->reflector()->reflectClass('MyClass');
$methodInfo    = $classInfo->getMethod('myMethod');
$parameterInfo = $methodInfo->getParameter('myParameter');

// Will fetch the language hint
var_dump($parameterInfo->getType());
```

## `ReflectionParameter->getType()`

This is compatible with the PHP 7 reflection API, and will return a `\Roave\BetterReflection\Reflection\ReflectionType`
instance.

## `ReflectionFunction->getReturnType()` and `ReflectionMethod->getReturnType()`

This is compatible with the PHP 7 reflection API, and will return a `\Roave\BetterReflection\Reflection\ReflectionType`
instance.
