# Upgrade Documentation

This document serves as a reference to upgrade your current 
BetterReflection installation if improvements, deprecations
or backwards compatibility (BC) breakages occur.

## 2.0.0

### PHP Version requirement raised to PHP 7.1.* and PHP 7.2.*

Due to the complexity of maintaining compatibility with multiple PHP
runtime environments and reflection API changes, the library now
only supports PHP 7.1.x and PHP 7.2.x

### Source locators now require additional dependencies

Due to major design and performance improvements, many of the
existing existing `Roave\BetterReflection\SourceLocator\Type\SourceLocator`
implementations now require you to pass in more parameters.

Following classes have a changed constructor signature:

 * `Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator`
 * `Roave\BetterReflection\SourceLocator\Type\AutoloadSourceLocator`

In order to easily comply with the new constructor signatures, you
can use the newly introduced `Roave\BetterReflection\BetterReflection`
kernel object:

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

Classes that you may have implemented and that
extend `Roave\BetterReflection\SourceLocator\Type\AbstractSourceLocator`
also need to adapt to the parent constructor signature.
