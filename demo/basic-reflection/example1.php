<?php

// Load a standard (internal) class

require_once __DIR__ . '/../../vendor/autoload.php';

use BetterReflection\Reflection\ReflectionClass;

$reflection = ReflectionClass::createFromName('stdClass');
echo $reflection->getName() . "\n"; // stdClass
echo ($reflection->isInternal() === true ? 'internal' : 'not internal') . "\n"; // internal

