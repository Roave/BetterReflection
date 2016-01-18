--TEST--
ReflectionClass::isSubclassOf() - non-existent class error
--FILE--
<?php require 'vendor/autoload.php';
class A {}
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName('A');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isSubclassOf('X'));

?>
--EXPECTF--
Fatal error: Uncaught ReflectionException: Class X does not exist in %s:5
Stack trace:
#0 %s(5): ReflectionClass->isSubclassOf('X')
#1 {main}
  thrown in %s on line 5
