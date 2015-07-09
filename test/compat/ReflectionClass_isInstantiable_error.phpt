--TEST--
ReflectionClass::IsInstantiable()
--FILE--
<?php
class privateCtorOld {
	private function privateCtorOld() {}
}
$reflectionClass = new ReflectionClass("privateCtorOld");

var_dump($reflectionClass->IsInstantiable('X'));
var_dump($reflectionClass->IsInstantiable(0, null));

?>
--EXPECTF--
Deprecated: Methods with the same name as their class will not be constructors in a future version of PHP; privateCtorOld has a deprecated constructor in %s on line %d

Warning: ReflectionClass::isInstantiable() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::isInstantiable() expects exactly 0 parameters, 2 given in %s on line %d
NULL
