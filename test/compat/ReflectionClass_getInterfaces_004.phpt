--TEST--
ReflectionClass::getInterfaces() - wrong param count
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
interface I {}
class C implements I {}
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getInterfaces(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getInterfaces('X'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getInterfaces(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getInterfaces(array(1,2,3)));
?>
--EXPECTF--
Warning: ReflectionClass::getInterfaces() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getInterfaces() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getInterfaces() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getInterfaces() expects exactly 0 parameters, 1 given in %s on line %d
NULL
