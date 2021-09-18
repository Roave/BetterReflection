<?php

// Load an autoloadable class

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;

require_once __DIR__ . '/../../vendor/autoload.php';

$reflection = (new BetterReflection())->reflector()->reflectClass(ReflectionClass::class);

echo $reflection->getName() . "\n"; // ReflectionClass
echo ($reflection->isInternal() === true ? 'internal' : 'not internal') . "\n"; // not internal
