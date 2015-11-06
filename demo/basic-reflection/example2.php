<?php
// Load an autoloadable class

require_once __DIR__ . '/../../vendor/autoload.php';

use BetterReflection\Reflection\ReflectionClass;

$reflection = ReflectionClass::createFromName(ReflectionClass::class);
var_dump($reflection->getName()); // BetterReflection\Reflection\ReflectionClass
var_dump($reflection->isInternal()); // false
