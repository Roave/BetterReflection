<?php

// Loading a specific file (not from autoloader)

require_once __DIR__ . '/../../vendor/autoload.php';

use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

$reflector = new ClassReflector(new AggregateSourceLocator([
    new SingleFileSourceLocator('assets/MyClass.php'),
]));

$reflection = $reflector->reflect('MyClass');

var_dump($reflection->getName()); // MyClass
var_dump($reflection->getProperty('foo')->isPrivate()); // true
var_dump($reflection->getProperty('foo')->getDocBlockTypeStrings()[0]); // string
var_dump($reflection->getMethod('getFoo')->getDocBlockReturnTypes()[0]->__toString()); // string

