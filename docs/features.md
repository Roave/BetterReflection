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
`\BetterReflection\Reflection\ReflectionType` instance (which does not
extend `\ReflectionType`). You can fetch the phpDocumentor Reflection Type
by using the `getTypeObject()` method:

```
$reflectionType = $parameterInfo->getType();
$phpDocumentorReflectionType = $reflectionType->getTypeObject();
```
