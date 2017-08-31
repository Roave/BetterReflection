<?php

// Load an autoloadable class

use Roave\BetterReflection\BetterReflection;

require_once __DIR__ . '/../../vendor/autoload.php';

$reflection = (new BetterReflection())->classReflector()->reflect(\stdClass::class);

echo $reflection->getName() . "\n"; // ReflectionClass
echo ($reflection->isInternal() === true ? 'internal' : 'not internal') . "\n"; // not internal
