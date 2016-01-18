--TEST--
ReflectionObject::getName() - basic function test
--FILE--
<?php require 'vendor/autoload.php';
$r0 = \BetterReflection\Reflection\ReflectionObject::createFromInstance();
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r0->getName());

$r1 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new stdClass);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r1->getName());

class C { }
$myInstance = new C;
$r2 = \BetterReflection\Reflection\ReflectionObject::createFromInstance($myInstance);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r2->getName());

$r3 = \BetterReflection\Reflection\ReflectionObject::createFromInstance($r2);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r3->getName());

?>
--EXPECTF--

Warning: ReflectionObject::__construct() expects exactly 1 parameter, 0 given in %s on line 2
string(0) ""
string(8) "stdClass"
string(1) "C"
string(16) "ReflectionObject"

