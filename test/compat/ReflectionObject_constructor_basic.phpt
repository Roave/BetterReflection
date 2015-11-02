--TEST--
ReflectionObject::__construct - basic function test
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';
$r1 = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new stdClass);
var_dump($r1);

class C { }
$myInstance = new C;
$r2 = \BetterReflection\Reflection\ReflectionObject::createFromInstance($myInstance);
var_dump($r2);

$r3 = \BetterReflection\Reflection\ReflectionObject::createFromInstance($r2);
var_dump($r3);
?>
--EXPECTF--
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(8) "stdClass"
}
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(1) "C"
}
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(16) "ReflectionObject"
}
