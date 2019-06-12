# Upgrade Documentation

This document serves as a reference to upgrade your current BetterReflection installation if improvements, deprecations
or backwards compatibility (BC) breakages occur.

## 4.0.0

### BC Breaks

* The following classes no longer implement `\Reflector`. If you need something that implements `\Reflector`, you should
  wrap the BetterReflection in the appropriate `\Roave\BetterReflection\Reflection\Adapter\*` class.
  * `\Roave\BetterReflection\Reflection\ReflectionClass`
  * `\Roave\BetterReflection\Reflection\ReflectionClassConstant`
  * `\Roave\BetterReflection\Reflection\ReflectionConstant`
  * `\Roave\BetterReflection\Reflection\ReflectionFunctionAbstract`
  * `\Roave\BetterReflection\Reflection\ReflectionFunction`
  * `\Roave\BetterReflection\Reflection\ReflectionMethod`
  * `\Roave\BetterReflection\Reflection\ReflectionObject`
  * `\Roave\BetterReflection\Reflection\ReflectionParameter`
  * `\Roave\BetterReflection\Reflection\ReflectionProperty`
* The `$stubber` parameter of `Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator` and
  `Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator` constructors is now required.

## 3.0.0

### BC breaks

* Method `Roave\BetterReflection\Reflection\ReflectionType::createFromType()` has been removed, use 
  `Roave\BetterReflection\Reflection\ReflectionType::createFromTypeAndReflector()` instead.
* Method `Roave\BetterReflection\Reflection\Adapter\ReflectionClass#getProperty()` throws an exception when a property 
  does not exist (to be compatible with core reflection).
* Method `Roave\BetterReflection\Reflection\Adapter\ReflectionObject#getProperty()` throws an exception when a property 
  does not exist (to be compatible with core reflection).

## 2.0.0

### Namespace change

The base namespace of the library changed from `BetterReflection` to `Roave\BetterReflection`. You may search for 
usages of the previous namespace with following regular expressions:

 * `/\s*use\s+(\\)?BetterReflection/`
 * `/[^A-Za-z0-9]+(\\)?BetterReflection/`
 
The found imports should be replaced with `Roave\BetterReflection` imports.
 
### PHP Version requirement raised to PHP 7.1.* and PHP 7.2.*

Due to the complexity of maintaining compatibility with multiple PHP runtime environments and reflection API changes, 
the library now only supports PHP 7.1.x and PHP 7.2.x

### Source locators now require additional dependencies

Due to major design and performance improvements, many of the existing 
`Roave\BetterReflection\SourceLocator\Type\SourceLocator` implementations now require you to pass in more parameters.

Following classes have a changed constructor signature:

 * `Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator`
 * `Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator`

In order to comply with the new constructor signatures, you can use the newly introduced 
`Roave\BetterReflection\BetterReflection` kernel object:

```php
<?php

use Composer\Autoload\ClassLoader;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

$betterReflection = new BetterReflection();
$astLocator       = $betterReflection->astLocator();

new AutoloadSourceLocator($astLocator);
new ClosureSourceLocator(function () {}, $betterReflection->phpParser());
new ComposerSourceLocator(new ClassLoader(), $astLocator);
new DirectoriesSourceLocator([__DIR__], $astLocator);
new EvaledCodeSourceLocator($astLocator);
new FileIteratorSourceLocator(new \FilesystemIterator(__DIR__), $astLocator);
new PhpInternalSourceLocator($astLocator);
new SingleFileSourceLocator(__FILE__, $astLocator);
```

Classes that you may have implemented and that extend `Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator`
also need to adapt to the parent constructor signature.

### `BetterReflection\Reflection\Exception\NotAString` removed

The `BetterReflection\Reflection\Exception\NotAString` exception was removed, as we now rely on PHP 7's 
`declare(strict_types=1)`

### `Roave\BetterReflection\Util\FindReflectionOnLine` constructor changed

`Roave\BetterReflection\Util\FindReflectionOnLine` now requires additional parameters. It is advisable to use the 
`Roave\BetterReflection\BetterReflection` kernel to get an instance of this class instead:

```php
<?php

use Roave\BetterReflection\BetterReflection;

$findReflectionOnLine = (new BetterReflection())->findReflectionsOnLine();
```

### `Roave\BetterReflection\Identifier\Identifier` constructor changed

`Roave\BetterReflection\Identifier\Identifier::__construct()` now requires the `$name` parameter to be a `string`.

A `BetterReflection\Reflection\Exception\NotAString` will no longer be thrown, while you will get a `TypeError` 
instead, should you not comply with this signature at call time.


### `Roave\BetterReflection\NodeCompiler` constructor changed

The internal `Roave\BetterReflection\NodeCompiler` class now requires a second mandatory constructor argument.

### `Roave\BetterReflection\Reflector\Reflector#reflect()` interface changed

The `Roave\BetterReflection\Reflector\Reflector#reflect()` now requires a `string` argument for `$identifierName`. You 
will need to change your own implementations of the interface.

### `BetterReflection\Reflection\ReflectionParameter#getDefaultValueAsString()` removed

`BetterReflection\Reflection\ReflectionParameter#getDefaultValueAsString()` was removed. This method was providing some 
sort of serialization of internal reflection data, and it opens possibilities for security issues if mishandled.

The equivalent replacement is to manually call `var_export($value, true)` instead, assuming that you know its intended 
usage context:

```php
<?php

use Roave\BetterReflection\BetterReflection;

function myFunction($myParameter = 'default value') {
    // ...
}

$defaultValue = (new BetterReflection())
    ->functionReflector()
    ->reflect('myFunction')
    ->getParameter('myParameter')
    ->getDefaultValue();

echo var_export($defaultValue, true);
```

### `BetterReflection\TypesFinder\FindTypeFromAst` was removed

The `BetterReflection\TypesFinder\FindTypeFromAst` utility was removed, as all AST nodes used by `BetterReflection` are 
now processed through a `PhpParser\NodeVisitor\NameResolver` visitor, which guarantees that the FQCN of the symbol is 
always available.

This change also allowed for massive performance improvement, as fewer repeated parsing operations have to be performed 
in order to discover node types.

### `BetterReflection\Reflection\ReflectionParameter#getTypeHint()` was removed

The `BetterReflection\Reflection\ReflectionParameter#getTypeHint()` was dropped, favoring just 
`BetterReflection\Reflection\ReflectionParameter#getType()` instead.

### `BetterReflection\Reflection\ReflectionParameter#setType()` now requires a `string`

The `BetterReflection\Reflection\ReflectionParameter#setType()` method now requires a `string` argument to be passed to 
it. The type will be detected from the given string.

### `BetterReflection\Reflection\ReflectionType` now works with `string` type definitions

The `BetterReflection\Reflection\ReflectionType` object used to work with `phpDocumentor` implementation details, but 
is now fully independent and only relying on `string` type definitions. Therefore:

 * `ReflectionType::getTypeObject()` was removed
 * `ReflectionType::createFromType()` now requires a `string` for the
   `$type` parameter

### `BetterReflection\Reflection\ReflectionFunctionAbstract#setReturnType()` now requires a `string`

The `BetterReflection\Reflection\ReflectionFunctionAbstract#setReturnType()` method now requires a `string` argument to 
be passed to it. The type will be detected from the given string.

### `Roave\BetterReflection\SourceLocator\Reflection\SourceStubber` now works with core `ReflectionClass`

The `Roave\BetterReflection\SourceLocator\Reflection\SourceStubber` is  now capable of working with just 
`ReflectionClass` instances from PHP core: it is no longer required to use `Zend\Code` instances, but the type hints 
also changed accordingly.

### `Roave\BetterReflection\Reflector\ClassReflector::buildDefaultReflector()` dropped

Since the `Roave\BetterReflection\BetterReflection` kernel was introduced, 
`Roave\BetterReflection\Reflector\ClassReflector::buildDefaultReflector()` was removed. The equivalent API is following:

```php
<?php

use Roave\BetterReflection\BetterReflection;

$reflector = (new BetterReflection())->classReflector();
```

### `Roave\BetterReflection\Reflection\Exception\PropertyNotPublic` dropped

The `Roave\BetterReflection\Reflection\Exception\PropertyNotPublic` was thrown when a non-accessible reflection 
property is being read or written to. Since this is "better" reflection, there is no need for calling anything like 
`setAccessible` on a `Roave\BetterReflection\Reflection\ReflectionProperty` instance. All properties are directly 
accessible, even if `private`, `protected` or dynamically defined.

### `Roave\BetterReflection\Reflector\FunctionReflector` constructor changed

The constructor of `Roave\BetterReflection\Reflector\FunctionReflector` changed in its required parameters. For generic 
`BetterReflection` usage, it is advisable to obtain a `Roave\BetterReflection\Reflector\FunctionReflector` from the 
`Roave\BetterReflection\BetterReflection` kernel:

```php
<?php

use Roave\BetterReflection\BetterReflection;

$reflector = (new BetterReflection())->functionReflector();
```

### `Roave\BetterReflection\SourceLocator\Ast\Locator` constructor changed

The constructor of `Roave\BetterReflection\SourceLocator\Ast\Locator` changed in its required parameters. For generic 
`BetterReflection` usage, it is advisable to obtain a `Roave\BetterReflection\SourceLocator\Ast\Locator` from the 
`Roave\BetterReflection\BetterReflection` kernel:

```php
<?php

use Roave\BetterReflection\BetterReflection;
$astLocator = (new BetterReflection())->astLocator();
```

### `Roave\BetterReflection\Reflection\Reflection*::createFromNode()` changes

Due to performance and type introspection requirements, the following methods changed their signature completely:

 * `Roave\BetterReflection\Reflection\ReflectionMethod::createFromNode()`
 * `Roave\BetterReflection\Reflection\ReflectionParameter::createFromNode()`
 * `Roave\BetterReflection\Reflection\ReflectionProperty::createFromNode()`

It is advisable to not call these methods directly, as they are likely to change in future as well. Instead, please use 
a corresponding `Roave\BetterReflection\Reflector\Reflector` object to instantiate them.

### `Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource` constructor changed

In order to aid in detecting the source of a located internal class or function, the 
`Roave\BetterReflection\SourceLocator\Located\InternalLocatedSource` now has an additional mandatory `$extensionName` 
parameter.
