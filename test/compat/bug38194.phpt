--TEST--
Reflection Bug #38194 (ReflectionClass::isSubclassOf() returns TRUE for the class itself)
--FILE--
<?php require 'vendor/autoload.php';
class Object { }
  
$objectClass= \BetterReflection\Reflection\ReflectionClass::createFromName('Object');
var_dump($objectClass->isSubclassOf($objectClass));
?>
--EXPECT--
bool(false)
