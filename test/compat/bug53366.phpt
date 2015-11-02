--TEST--
Bug #53366 (Reflection doesnt get dynamic property value from getProperty())
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';

class UserClass {
}

$myClass = new UserClass;
$myClass->id = 1000;

$reflect = \BetterReflection\Reflection\ReflectionObject::createFromInstance($myClass);

var_dump($reflect->getProperty('id'));
var_dump($reflect->getProperty('id')->getValue($myClass));

?>
--EXPECTF--
object(ReflectionProperty)#%d (2) {
  ["name"]=>
  string(2) "id"
  ["class"]=>
  string(9) "UserClass"
}
int(1000)
