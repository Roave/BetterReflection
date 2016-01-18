--TEST--
ReflectionClass::getParentClass()
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';
class A {}
class B extends A {}

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('B');
$parent = $rc->getParentClass();
$grandParent = $parent->getParentClass();
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($parent, $grandParent);

echo "\nTest bad params:\n";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getParentClass(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getParentClass('x'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getParentClass('x', 123));

?>
--EXPECTF--
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(1) "A"
}
bool(false)

Test bad params:

Warning: ReflectionClass::getParentClass() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getParentClass() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getParentClass() expects exactly 0 parameters, 2 given in %s on line %d
NULL
