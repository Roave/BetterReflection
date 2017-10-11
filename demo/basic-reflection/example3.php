<?php

// Loading a specific file (not from autoloader)

require_once __DIR__ . '/../../vendor/autoload.php';

use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\Reflector\ClassReflector;
use Rector\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Rector\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

$reflector = new ClassReflector(new AggregateSourceLocator([
    new SingleFileSourceLocator(__DIR__ . '/assets/MyClass.php', (new BetterReflection())->astLocator()),
]));

$reflection = $reflector->reflect('MyClass');

echo $reflection->getName() . "\n"; // MyClass
echo ($reflection->getProperty('foo')->isPrivate() === true ? 'private' : 'not private') . "\n"; // private
echo $reflection->getProperty('foo')->getDocBlockTypeStrings()[0] . "\n"; // string
echo $reflection->getMethod('getFoo')->getDocBlockReturnTypes()[0]->__toString() . "\n"; // string

