--TEST--
ReflectionObject::isInstance() - invalid params
--FILE--
<?php require 'vendor/autoload.php';
class X {}
$instance = new X;
$ro = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new X);

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ro->isInstance());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ro->isInstance($instance, $instance));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ro->isInstance(1));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ro->isInstance(1.5));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ro->isInstance(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ro->isInstance('X'));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($ro->isInstance(null));

?>
--EXPECTF--
Warning: ReflectionClass::isInstance() expects exactly 1 parameter, 0 given in %s on line 6
NULL

Warning: ReflectionClass::isInstance() expects exactly 1 parameter, 2 given in %s on line 7
NULL

Warning: ReflectionClass::isInstance() expects parameter 1 to be object, integer given in %s on line 8
NULL

Warning: ReflectionClass::isInstance() expects parameter 1 to be object, float given in %s on line 9
NULL

Warning: ReflectionClass::isInstance() expects parameter 1 to be object, boolean given in %s on line 10
NULL

Warning: ReflectionClass::isInstance() expects parameter 1 to be object, string given in %s on line 11
NULL

Warning: ReflectionClass::isInstance() expects parameter 1 to be object, null given in %s on line 12
NULL
