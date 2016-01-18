--TEST--
ReflectionMethod::getDocComment() errors
--FILE--
<?php require 'vendor/autoload.php';
class C { function f() {} }
$rc = \BetterReflection\Reflection\ReflectionMethod::createFromName('C::f');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDocComment(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getDocComment('X'));
?>
--EXPECTF--
Warning: ReflectionFunctionAbstract::getDocComment() expects exactly 0 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionFunctionAbstract::getDocComment() expects exactly 0 parameters, 1 given in %s on line %d
NULL
