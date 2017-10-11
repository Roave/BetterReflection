--TEST--
Reflecting a user-defined class by name
--FILE--
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Rector\BetterReflection\Reflection\ReflectionClass;

$classInfo = ReflectionClass::createFromName(ReflectionClass::class);

var_dump($classInfo->getName());
var_dump($classInfo->isInternal());
?>
--EXPECT--
string(49) "Rector\BetterReflection\Reflection\ReflectionClass"
bool(false)
