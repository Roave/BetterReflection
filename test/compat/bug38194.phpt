--TEST--
Reflection Bug #38194 (ReflectionClass::isSubclassOf() returns TRUE for the class itself)
--FILE--
<?php require 'vendor/autoload.php';
class Object { }
  
$objectClass= \BetterReflection\Reflection\ReflectionClass::createFromName('Object');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($objectClass->isSubclassOf($objectClass));
?>
--EXPECT--
bool(false)
