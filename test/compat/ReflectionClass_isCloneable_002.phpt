--TEST--
Testing ReflectionClass::isCloneable() with non instantiable objects
--FILE--
<?php require 'vendor/autoload.php';

trait foo {
}
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName('foo');
var_dump($obj->isCloneable());

abstract class bar {
}
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName('bar');
var_dump($obj->isCloneable());

interface baz {
}
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName('baz');
var_dump($obj->isCloneable());

?>
--EXPECT--
bool(false)
bool(false)
bool(false)
