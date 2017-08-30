# Feature examples

## Reflecting on classes not yet loaded

In most cases, creating reflections using a built-in `SourceLocator` or the
`ReflectionClass::createFromName()` technique don't attempt to load code. This
means if a class has not already been declared, you can safely assume that
Better Reflection won't load the class.

Note that if the class is *already* loaded, then this Better Reflection does
not then *unload* that class - this is not currently possible in PHP.

## Reflecting on things within a string

See [Loading a class from a string](https://github.com/Roave/BetterReflection/tree/master/docs/usage.md#Loading-a-class-from-a-string)

## Analysing types from DocBlocks

See [types documentation](https://github.com/Roave/BetterReflection/tree/master/docs/types.md)

## AST extraction from Reflections

See [AST extraction documentation](https://github.com/Roave/BetterReflection/tree/master/docs/ast-extraction.md)

## PHP 7 Parameter Type Declarations and Return Type Declarations

These act in the same way as the core reflection API, except they return a
`\Roave\BetterReflection\Reflection\ReflectionType` instance (which does not
extend `\ReflectionType`).

```
$reflectionType = $parameterInfo->getType();
```

However, Better Reflection also gives the ability to change, and remove type
declarations. Removing these types might be useful if you want to make code
written for PHP 7 work in PHP 5 for example, and setting new types might
do the opposite. For instance, you might want to set the PHP 7 return type
declaration to that defined in the PHP DocBlock.

```
// Change a function to ensure it returns an integer
$functionInfo = (new SetFunctionReturnType())($functionInfo, 'int');

// If there is only one type defined in the DocBlock, set it as the return type
$returnTypes = $functionInfo->getDocBlockReturnTypes();
if (count($returnTypes) === 1) {
    $functionInfo = (new SetFunctionReturnType())($functionInfo, (string) $returnTypes[0], false);
}

// Remove the return type declaration
$functionInfo = (new RemoveFunctionReturnType())($functionInfo);
```

You can do similar things with parameter types also:

```
(new SetParameterType())($parameterInfo, 'int');
(new RemoveParameterType())($parameterInfo);
```
