--TEST--
ReflectionClass::getMethods() - invalid arguments
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("ReflectionClass");
echo "\nTest invalid arguments:";
$rc->getMethods('X');
$rc->getMethods('X', true);

?>
--EXPECTF--
Test invalid arguments:
Warning: ReflectionClass::getMethods() expects parameter 1 to be integer, string given in %s on line 4

Warning: ReflectionClass::getMethods() expects at most 1 parameter, 2 given in %s on line 5
