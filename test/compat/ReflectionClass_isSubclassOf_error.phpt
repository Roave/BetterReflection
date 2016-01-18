--TEST--
ReflectionClass::isSubclassOf() - invalid number of parameters
--FILE--
<?php require 'vendor/autoload.php';
class A {}
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('A');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isSubclassOf());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isSubclassOf('A',5));

?>
--EXPECTF--
Warning: ReflectionClass::isSubclassOf() expects exactly 1 parameter, 0 given in %s on line 5
NULL

Warning: ReflectionClass::isSubclassOf() expects exactly 1 parameter, 2 given in %s on line 6
NULL
