<?php

// Load a standard (internal) class

require_once __DIR__ . '/../../vendor/autoload.php';

use BetterReflection\Reflection\ReflectionClass;

$reflection = ReflectionClass::createFromName('stdClass');
var_dump($reflection->getName()); // stdClass
var_dump($reflection->isInternal()); // true

