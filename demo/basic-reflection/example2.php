<?php
// Load an autoloadable class

require_once __DIR__ . '/../../vendor/autoload.php';

use Roave\BetterReflection\Reflection\ReflectionClass;

$reflection = ReflectionClass::createFromName(ReflectionClass::class);
echo $reflection->getName() . "\n"; // ReflectionClass
echo ($reflection->isInternal() === true ? 'internal' : 'not internal') . "\n"; // not internal

