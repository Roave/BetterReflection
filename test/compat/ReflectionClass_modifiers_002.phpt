--TEST--
Modifiers - wrong param count
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';
class C {}
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("C");
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isFinal('X'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isInterface(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isAbstract(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getModifiers(array(1,2,3)));

?>
--EXPECTF--
Warning: ReflectionClass::isFinal() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::isInterface() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::isAbstract() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getModifiers() expects exactly 0 parameters, 1 given in %s on line %d
NULL
