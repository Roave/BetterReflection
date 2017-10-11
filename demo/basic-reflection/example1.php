<?php

// Load a standard (internal) class

require_once __DIR__ . '/../../vendor/autoload.php';

use Rector\BetterReflection\BetterReflection;

$reflection = (new BetterReflection())->classReflector()->reflect(\stdClass::class);

echo $reflection->getName() . "\n"; // stdClass
echo ($reflection->isInternal() === true ? 'internal' : 'not internal') . "\n"; // internal
