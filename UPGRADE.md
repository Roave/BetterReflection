# Upgrade Documentation

This document serves as a reference to upgrade your current 
BetterReflection installation if improvements, deprecations
or backwards compatibility (BC) breakages occur.

## 2.0.0

### Namespace change

The base namespace of the library changed from `BetterReflection`
to `Rector\BetterReflection`.
You may search for usages of the previous namespace with following
regular expressions:

 * `/\s*use\s+(\\)?BetterReflection/`
 * `/[^A-Za-z0-9]+(\\)?BetterReflection/`
 
The found imports should be replaced with `Rector\BetterReflection`
imports.
 
### PHP Version requirement raised to PHP 7.1.* and PHP 7.2.*

Due to the complexity of maintaining compatibility with multiple PHP
runtime environments and reflection API changes, the library now
only supports PHP 7.1.x and PHP 7.2.x

### Source locators now require additional dependencies

Due to major design and performance improvements, many of the
existing existing `Rector\BetterReflection\SourceLocator\Type\SourceLocator`
implementations now require you to pass in more parameters.

Following classes have a changed constructor signature:

 * `Rector\BetterReflection\SourceLocator\Type\AbstractSourceLocator`
 * `Rector\BetterReflection\SourceLocator\Type\AutoloadSourceLocator`

In order to easily comply with the new constructor signatures, you
can use the newly introduced `Rector\BetterReflection\BetterReflection`
kernel object:

```php
<?php

use Composer\Autoload\ClassLoader;
use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\ComposerSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

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

Classes that you may have implemented and that
extend `Rector\BetterReflection\SourceLocator\Type\AbstractSourceLocator`
also need to adapt to the parent constructor signature.

### `BetterReflection\Reflection\Exception\NotAString` removed

The `BetterReflection\Reflection\Exception\NotAString` exception was
removed, as we now rely on PHP7's `declare(strict_types=1)`

### `Rector\BetterReflection\Util\FindReflectionOnLine` constructor changed

`Rector\BetterReflection\Util\FindReflectionOnLine` now requires additional
parameters. It is advisable to simply use
the `Rector\BetterReflection\BetterReflection` kernel to get an instance of
this class instead:

```php
<?php

use Rector\BetterReflection\BetterReflection;

$findReflectionOnLine = (new BetterReflection())->findReflectionsOnLine();
```

### `Rector\BetterReflection\Identifier\Identifier` constructor changed

`Rector\BetterReflection\Identifier\Identifier::__construct()` now requires
the `$name` parameter to be a `string`.

A `BetterReflection\Reflection\Exception\NotAString` will no longer be thrown,
while you will get a `TypeError` instead, should you not comply with this
signature at call time.


### `Rector\BetterReflection\NodeCompiler` constructor changed

The internal `Rector\BetterReflection\NodeCompiler` class now requires
a second mandatory constructor argument.

### `Rector\BetterReflection\Reflector\Reflector#reflect()` interface changed

The `Rector\BetterReflection\Reflector\Reflector#reflect()` now requires
a `string` argument for `$identifierName`. You will need to change
your own implementations of the interface.

### `BetterReflection\Reflection\ReflectionParameter#getDefaultValueAsString()` removed

`BetterReflection\Reflection\ReflectionParameter#getDefaultValueAsString()`
was removed. This method was providing some sort of serialization of internal
reflection data, and it opens possibilities for security issues if mishandled.

The equivalent replacement is to manually call `var_export($value, true)`
instead, assuming that you know its intended usage context:

```php
<?php

use Rector\BetterReflection\BetterReflection;

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

The `BetterReflection\TypesFinder\FindTypeFromAst` utility was removed,
as all AST nodes used by `BetterReflection` are now processed through
a `PhpParser\NodeVisitor\NameResolver` visitor, which guarantees that
the FQCN of the symbol is always available.

This change also allowed for massive performance improvement, as fewer
repeated parsing operations have to be performed in order to discover
node types.

### `BetterReflection\Reflection\ReflectionParameter#getTypeHint()` was removed

The `BetterReflection\Reflection\ReflectionParameter#getTypeHint()` was dropped,
favoring just `BetterReflection\Reflection\ReflectionParameter#getType()` instead.

### `BetterReflection\Reflection\ReflectionParameter#setType()` now requires a `string`

The `BetterReflection\Reflection\ReflectionParameter#setType()` method now
requires a `string` argument to be passed to it. The type will be detected
from the given string.

### `BetterReflection\Reflection\ReflectionType` now works with `string` type definitions

The `BetterReflection\Reflection\ReflectionType` object used to work with
`phpDocumentor` implementation details, but is now fully independent and
only relying on `string` type definitions. Therefore:

 * `ReflectionType::getTypeObject()` was removed
 * `ReflectionType::createFromType()` now requires a `string` for the
   `$type` parameter

### `BetterReflection\Reflection\ReflectionFunctionAbstract#setReturnType()` now requires a `string`

The `BetterReflection\Reflection\ReflectionFunctionAbstract#setReturnType()` method
now requires a `string` argument to be passed to it. The type will be
detected from the given string.

### `Rector\BetterReflection\SourceLocator\Reflection\SourceStubber` now works with core `ReflectionClass`

The `Rector\BetterReflection\SourceLocator\Reflection\SourceStubber` is 
now capable of working with just `ReflectionClass` instances from PHP
core: it is no longer required to use `Zend\Code` instances, but the
type hints also changed accordingly.

### `Rector\BetterReflection\Reflector\ClassReflector::buildDefaultReflector()` dropped

Since the `Rector\BetterReflection\BetterReflection` kernel was introduced,
`Rector\BetterReflection\Reflector\ClassReflector::buildDefaultReflector()` was
removed. The equivalent API is following:

```php
<?php

use Rector\BetterReflection\BetterReflection;

$reflector = (new BetterReflection())->classReflector();
```

### `Rector\BetterReflection\Reflection\Exception\PropertyNotPublic` dropped

The `Rector\BetterReflection\Reflection\Exception\PropertyNotPublic` was
thrown when a non-accessible reflection property is being read or written
to. Since this is "better" reflection, there is no need for calling anything
like `setAccessible` on a `Rector\BetterReflection\Reflection\ReflectionProperty`
instance. All properties are directly accessible, even if `private`, `protected`
or dynamically defined.

### `Rector\BetterReflection\Reflector\FunctionReflector` constructor changed

The constructor of `Rector\BetterReflection\Reflector\FunctionReflector`
changed in its required parameters. For generic `BetterReflection` usage,
it is advisable to simply obtain a `Rector\BetterReflection\Reflector\FunctionReflector`
from the `Rector\BetterReflection\BetterReflection` kernel:

```php
<?php

use Rector\BetterReflection\BetterReflection;

$reflector = (new BetterReflection())->functionReflector();
```

### `Rector\BetterReflection\SourceLocator\Ast\Locator` constructor changed

The constructor of `Rector\BetterReflection\SourceLocator\Ast\Locator`
changed in its required parameters. For generic `BetterReflection` usage,
it is advisable to simply obtain a `Rector\BetterReflection\SourceLocator\Ast\Locator`
from the `Rector\BetterReflection\BetterReflection` kernel:

```php
<?php

use Rector\BetterReflection\BetterReflection;
$astLocator = (new BetterReflection())->astLocator();
```

### `Rector\BetterReflection\Reflection\Reflection*::createFromNode()` changes

Due to performance and type introspection requirements, the following methods
changed their signature completely:

 * `Rector\BetterReflection\Reflection\ReflectionMethod::createFromNode()`
 * `Rector\BetterReflection\Reflection\ReflectionParameter::createFromNode()`
 * `Rector\BetterReflection\Reflection\ReflectionProperty::createFromNode()`

It is advisable to not call these methods directly, as they are likely to change
in future as well. Instead, please use a
corresponding `Rector\BetterReflection\Reflector\Reflector` object to
instantiate them.

### `Rector\BetterReflection\SourceLocator\Located\InternalLocatedSource` constructor changed

In order to aid in detecting the source of a located internal class or function,
the `Rector\BetterReflection\SourceLocator\Located\InternalLocatedSource` now has
an additional mandatory `$extensionName` parameter.
