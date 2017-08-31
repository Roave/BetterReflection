# Extracting AST from reflections

## Method and Function body AST

Given a `ReflectionMethod` or `ReflectionFunction`, you can extract the content
(within curly braces) as an Abstract Syntax Tree, or as unparsed AST (i.e. as
normal code). This allows you to easily grab the AST from a specific method with
very few lines of code:

```php
<?php

$classInfo = (new \Roave\BetterReflection\BetterReflection())
    ->classReflector()
    ->reflect(\Foo\Bar\MyClass::class);

// Retrieves the AST statements array *within* the method's curly braces
$ast = $classInfo->getMethod('foo')->getBodyAst();
$php = $classInfo->getMethod('foo')->getBodyCode();
```

### Note on extracting code using `getBodyCode()`

The code returned by the `getBodyCode()` method is pretty-printed using the
AST unparser in the `PhpParser` library. This means the actual code returned
here may be laid out differently to the original function or method. See the
code documentation for details on how to provide a custom printer to override
the default behaviour.

## Fetching AST representation of a class or function

It is possible to fetch an AST representation of a class or function using the
`getAst()` method on a `ReflectionClass` and `ReflectionFunction`.

```php
<?php

$classInfo = (new \Roave\BetterReflection\BetterReflection())
    ->classReflector()
    ->reflect(\Foo\Bar\MyClass::class);

// Retrieves AST nodes for the entire class (including the class definition)
$ast = $classInfo->getAst();
```

Note that the node returned will be the top level of the class, thus including
the actual class definition itself.
