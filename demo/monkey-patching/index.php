<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\Util\Autoload\ClassLoader;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader;

$loader = new ClassLoader(FileCacheLoader::defaultFileCacheLoader(__DIR__));

// Create the reflection first (without loading)
$classInfo = (new \Roave\BetterReflection\Reflector\DefaultReflector(
    new SingleFileSourceLocator(__DIR__ . '/MyClass.php', (new BetterReflection())->astLocator())
))->reflectClass('MyClass');
$loader->addClass($classInfo);

// Override the body...!
$classInfo->getMethod('foo')->setBodyFromClosure(function () {
    return 4;
});

$c = new MyClass();
echo $c->foo() . "\n"; // should be 4...!?!??
