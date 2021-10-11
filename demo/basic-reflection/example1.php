<?php

// Load a standard (internal) class

require_once __DIR__ . '/../../vendor/autoload.php';

use Roave\BetterReflection\BetterReflection;

$reflection = (new BetterReflection())->reflector()->reflectClass(\stdClass::class);

echo $reflection->getName() . "\n"; // stdClass
echo ($reflection->isInternal() === true ? 'internal' : 'not internal') . "\n"; // internal
