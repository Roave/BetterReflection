--TEST--
Reflecting an internal class by name
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

$classInfo = \Roave\BetterReflection\Reflection\ReflectionClass::createFromName('stdClass');

var_dump($classInfo->getName());
var_dump($classInfo->isInternal());
?>
--EXPECT--
string(8) "stdClass"
bool(true)
