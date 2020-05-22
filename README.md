Better Reflection
=================

[![Build status](https://github.com/Roave/BetterReflection/workflows/Build/badge.svg?branch=master)](https://github.com/Roave/BetterReflection/actions?query=workflow%3ABuild+branch%3Amaster)
[![Type Coverage](https://shepherd.dev/github/Roave/BetterReflection/coverage.svg)](https://shepherd.dev/github/Roave/BetterReflection)
[![Latest Stable Version](https://poser.pugx.org/roave/better-reflection/v/stable)](https://packagist.org/packages/roave/better-reflection)
[![License](https://poser.pugx.org/roave/better-reflection/license)](https://packagist.org/packages/roave/better-reflection)

Better Reflection is a reflection API that aims to improve and provide more features than PHP's built-in
[reflection API](https://php.net/manual/en/book.reflection.php).

## Why is it better?

* You can reflect on classes that are not already loaded, without loading them
* Ability to reflect on classes directly from a string of PHP code
* Better Reflection analyses the DocBlocks (using [phpdocumentor/type-resolver](https://github.com/phpDocumentor/TypeResolver))
* Reflecting directly on closures
* Ability to extract AST from methods and functions
* Ability to return AST representation of a class or function
* Fetch return type declaration and parameter type declarations in PHP 7 code
* Change or remove PHP 7 parameter type and return type declarations from methods and functions
* Change the body of a function or method to do something different
* *Moar stuff coming soon!*

Be sure to read more in the [feature documentation](docs/features.md).

## Installation

Require using composer:

```shell
$ composer require roave/better-reflection
```

## Usage

```php
<?php

use Roave\BetterReflection\BetterReflection;

$classInfo = (new BetterReflection())
    ->classReflector()
    ->reflect(\Foo\Bar\MyClass::class);
```

## Documentation

* [Compatibility with core Reflection API](docs/compatibility.md)
* [Basic usage instructions](docs/usage.md)
* [Using types](docs/types.md)
* [The features](docs/features.md)
* [Test suite](https://github.com/Roave/BetterReflection/blob/master/test/README.md)
* [AST extraction](docs/ast-extraction.md)
* [Reflection modification](docs/reflection-modification.md)

## Upgrading

Please refer to the [Upgrade Documentation](UPGRADE.md) documentation to see what is required to upgrade your installed
`BetterReflection` version.

## Limitations

* PHP cannot autoload functions, therefore we cannot statically reflect functions

## License

This package is released under the [MIT license](LICENSE).

## Contributing

If you wish to contribute to the project, please read the [CONTRIBUTING notes](CONTRIBUTING.md).
