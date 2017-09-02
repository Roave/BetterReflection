Better Reflection
=================

[![Build Status](https://travis-ci.org/Roave/BetterReflection.svg?branch=master)](https://travis-ci.org/Roave/BetterReflection) [![Build Status](https://ci.appveyor.com/api/projects/status/github/Roave/BetterReflection?svg=true&branch=master)](https://ci.appveyor.com/project/Ocramius/betterreflection-4jx2w) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Roave/BetterReflection/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Roave/BetterReflection/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/Roave/BetterReflection/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Roave/BetterReflection/?branch=master) [![Latest Stable Version](https://poser.pugx.org/roave/better-reflection/v/stable)](https://packagist.org/packages/roave/better-reflection) [![License](https://poser.pugx.org/roave/better-reflection/license)](https://packagist.org/packages/roave/better-reflection)

Better Reflection is a reflection API that aims to improve and provide more
features than PHP's built-in [reflection API](http://php.net/manual/en/book.reflection.php).

## Why is it better?

* You can reflect on classes that are not already loaded, without loading them
* Ability to reflect on classes directly from a string of PHP code
* Better Reflection analyses the DocBlocks (using [phpdocumentor/type-resolver](https://github.com/phpDocumentor/TypeResolver))
* Reflecting directly on closures
* Ability to extract AST from methods and functions
* Ability to return AST representation of a class or function
* Fetch return type declaration and parameter type declarations in PHP 7 code (even when running PHP 5!)
* Change or remove PHP 7 parameter type and return type declarations from methods and functions
* Change the body of a function or method to do something different
* *Moar stuff coming soon!*

Be sure to read more in the [feature documentation](https://github.com/Roave/BetterReflection/tree/master/docs/features.md).

## Installation

Simply require using composer:

```shell
$ composer require roave/better-reflection
```

## Usage

```php
use Roave\BetterReflection\Reflection\ReflectionClass;

$classInfo = ReflectionClass::createFromName('Foo\Bar\MyClass');
```

## Changes in BR 2.0

 * Changed instantiation of `FindReflectionOnLine` utility from `new FindReflectionOnLine()` to `FindReflectionOnLine::buildDefaultFinder()`. `FindReflectionOnLine` constructor now requires a `SourceLocator` parameter.
 * Minimum PHP 7.1
 * `NotAString` exception class is now gone
 * `Identifier` class requires string for `$name` parameter
 * `CompilerContext` requires second parameter (no longer optional, but still `null`able)
 * `Reflector` now requires a `string` for `$identiferName` parameter
 * Namespace moved to `\Roave\BetterReflection` instead of just `\BetterReflection`
 * `ReflectionParameter#getDefaultValueAsString` is deprecated in favour of using `var_export` directly.
 * `FindTypeFromAst` was removed
 * `ReflectionParameter::getTypeHint()` was removed
 * `ReflectionParameter::setType()` now requires `string` for `$newParameterType` parameter
 * `ReflectionType::getTypeObject()` was removed
 * `ReflectionType::createFromType()` now requires `string` for `$type` parameter
 * `ReflectionFunctionAbstract::setReturnType()` now requires `string` for `$newReturnType` parameter
 * `SourceStubber::__invoke()` now requires `\ReflectionClass` for `$classReflection` parameter
 * `Roave\BetterReflection\Reflector\FunctionReflector::__construct` now has mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Ast\Locator::__construct` now has mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator::__construct` now has mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator::__construct` now has mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator::__construct` has additional mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator::__construct` has additional mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator::__construct` has additional mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator::__construct` now has mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator::__construct` has additional mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator::__construct` now has mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator::__construct` has additional mandatory constructor arguments
 * `Roave\BetterReflection\SourceLocator\Type\StringSourceLocator::__construct` has additional mandatory constructor arguments
 * `Roave\BetterReflection\Util\FindReflectionOnLine::__construct` now has mandatory constructor arguments
 * `Roave\BetterReflection\Reflector\ClassReflector::buildDefaultReflector()` was dropped, please use the newly
   introduced `Roave\BetterReflection\BetterReflection#classReflector()` instead
 * `Roave\BetterReflection\Util\FindReflectionOnLine::buildDefaultFinder()` was dropped, please use the newly
   introduced `Roave\BetterReflection\BetterReflection#findReflectionOnLine()` instead
 * `Roave\BetterReflection\Reflection\Exception\PropertyNotPublic` was dropped, all properties are accessible now 

## More documentation

* [Compatibility with core Reflection API](https://github.com/Roave/BetterReflection/tree/master/docs/compatibility.md)
* [Basic usage instructions](https://github.com/Roave/BetterReflection/tree/master/docs/usage.md)
* [Using types](https://github.com/Roave/BetterReflection/tree/master/docs/types.md)
* [The features](https://github.com/Roave/BetterReflection/tree/master/docs/features.md)
* [Test suite](https://github.com/Roave/BetterReflection/blob/master/test/README.md)
* [AST extraction](https://github.com/Roave/BetterReflection/tree/master/docs/ast-extraction.md)
* [Reflection modification](https://github.com/Roave/BetterReflection/tree/master/docs/reflection-modification.md)

## Limitations

* PHP cannot autoload functions, therefore we cannot statically reflect functions
* Using `ReflectionClass::createFromName()` makes [some assumptions](https://github.com/Roave/BetterReflection/tree/master/docs/usage.md#basic-reflection). Alternative reflection techniques are possible to overcome this.

## Authors

* [James Titcumb](https://github.com/asgrim)
* [Marco Pivetta](https://github.com/Ocramius)
* [Gary Hockin](https://github.com/GeeH)

## License

This package is released under the [MIT license](LICENSE).

## Contributing

If you wish to contribute to the project, please read the [CONTRIBUTING notes](CONTRIBUTING.md).
