--TEST--
ReflectionClass::getParentClass()
--CREDITS--
Michelangelo van Dam <dragonbe@gmail.com>
#testfest roosendaal on 2008-05-10
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';

class Foo {}

class Bar extends Foo {}

$rc1 = \BetterReflection\Reflection\ReflectionClass::createFromName("Bar");
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc1->getParentClass());
?>

--EXPECTF--
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(3) "Foo"
}
