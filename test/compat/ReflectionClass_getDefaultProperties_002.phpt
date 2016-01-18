--TEST--
ReflectionClass::getDefaultProperties(), ReflectionClass::getStaticProperties() - wrong param count
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';
interface I {}
class C implements I {}
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('C');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDefaultProperties(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDefaultProperties('X'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDefaultProperties(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDefaultProperties(array(1,2,3)));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getStaticProperties(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getStaticProperties('X'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getStaticProperties(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getStaticProperties(array(1,2,3)));

?>
--EXPECTF--
Warning: ReflectionClass::getDefaultProperties() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getDefaultProperties() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getDefaultProperties() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getDefaultProperties() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getStaticProperties() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getStaticProperties() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getStaticProperties() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionClass::getStaticProperties() expects exactly 0 parameters, 1 given in %s on line %d
NULL
