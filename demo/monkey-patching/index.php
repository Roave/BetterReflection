<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Rector\BetterReflection\Util\Autoload\ClassLoader;
use Rector\BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader;

$loader = new ClassLoader(FileCacheLoader::defaultFileCacheLoader(__DIR__));

// Create the reflection first (without loading)
$classInfo = (new ClassReflector(
    new SingleFileSourceLocator(__DIR__ . '/MyClass.php', (new BetterReflection())->astLocator())
))->reflect('MyClass');
$loader->addClass($classInfo);

// Override the body...!
$classInfo->getMethod('foo')->setBodyFromClosure(function () {
    return 4;
});

$c = new MyClass();
echo $c->foo() . "\n"; // should be 4...!?!??
