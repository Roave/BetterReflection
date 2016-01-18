--TEST--
Testing ReflectionClass::isCloneable() with non instantiable objects
--FILE--
<?php require 'vendor/autoload.php';

trait foo {
}
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName('foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());

abstract class bar {
}
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName('bar');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());

interface baz {
}
$obj = \BetterReflection\Reflection\ReflectionClass::createFromName('baz');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isCloneable());

?>
--EXPECT--
bool(false)
bool(false)
bool(false)
