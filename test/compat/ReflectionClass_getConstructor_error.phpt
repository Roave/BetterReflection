--TEST--
ReflectionClass::getConstructor() - bad params
--FILE--
<?php require 'vendor/autoload.php';
class C {}
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getConstructor(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getConstructor('X'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getConstructor(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getConstructor(array(1,2,3)));
?>
--EXPECTF--
Warning: ReflectionClass::getConstructor() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getConstructor() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getConstructor() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getConstructor() expects exactly 0 parameters, 1 given in %s on line %d
NULL
