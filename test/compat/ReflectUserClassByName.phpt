--TEST--
Reflecting a user-defined class by name
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use BetterReflection\Reflection\ReflectionClass;

$classInfo = ReflectionClass::createFromName(ReflectionClass::class);

var_dump($classInfo->getName());
var_dump($classInfo->isInternal());
?>
--EXPECT--
string(43) "BetterReflection\Reflection\ReflectionClass"
bool(false)
