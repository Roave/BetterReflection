# Using Types

When you have a `ReflectionParameter`, you can determine types in the following
ways:

```php
class MyClass
{
    /**
     * @param array $myMethod
     */
    public function myMethod(array $myMethod = [])
    {
        // ... stuff ...
    }
}
```

```php

$classInfo = ReflectionClass::createFromName('MyClass');
$methodInfo = $classInfo->getMethod('myMethod');
$parameterInfo = $methodInfo->getParameter('myParameter');

// Will fetch a Type object for the language hint
var_dump($parameterInfo->getTypeHint());

// Will fetch an array of Type objects for the typehint in the DocBlock
var_dump($parameterInfo->getDocBlockTypes());

// Will fetch an array of strings describing the DocBlock type hints
var_dump($parameterInfo->getDocBlockTypeStrings());
```

## `getTypeHint`

The `getTypeHint()` method retrieves the language type hint, which there will
only ever be one of (in current versions of PHP). This method will therefore
return a single instance of a `Type` object (see below).

## `getDocBlockTypes`

The `getDocBlockTypes()` method will return an array of type hints that are
extracted from the DocBlock. These are read by a phpDocumentor component, and
so this returns an array of `Type` objects (see below).

## `getDocBlockTypeStrings`

This is simply a convenience wrapper to fetch the types named as strings.

## `ReflectionParameter->getType()`

This is compatible with the PHP 7 reflection API, and will return a
`\Roave\BetterReflection\Reflection\ReflectionType` instance.

## `ReflectionFunction->getReturnType()` and `ReflectionMethod->getReturnType()`

This is compatible with the PHP 7 reflection API, and will return a
`\Roave\BetterReflection\Reflection\ReflectionType` instance.

## The `Type` object

The `Type` objects are actually types provided by the `phpdocumentor/type-resolver`
library, which provides different types for describing PHP's internal types as
well as classes, callables and so on. For more information, head over to
[phpDocumentor/TypeResolver](https://github.com/phpDocumentor/TypeResolver) for
this excellent library!
