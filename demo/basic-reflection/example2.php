<?php

// Load an autoloadable class

use Rector\BetterReflection\BetterReflection;
use Rector\BetterReflection\Reflection\ReflectionClass;

require_once __DIR__ . '/../../vendor/autoload.php';

$reflection = (new BetterReflection())->classReflector()->reflect(ReflectionClass::class);

echo $reflection->getName() . "\n"; // ReflectionClass
echo ($reflection->isInternal() === true ? 'internal' : 'not internal') . "\n"; // not internal
