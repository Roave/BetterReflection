--TEST--
Reflection and Traits
--FILE--
<?php require 'vendor/autoload.php';

abstract class foo {
}

trait bar {
	
}

final class baz {
	
}

$x = \BetterReflection\Reflection\ReflectionClass::createFromName('foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($x->isTrait());

$x = \BetterReflection\Reflection\ReflectionClass::createFromName('bar');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($x->isTrait());

$x = \BetterReflection\Reflection\ReflectionClass::createFromName('baz');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($x->isTrait());

?>
--EXPECT--
bool(false)
bool(true)
bool(false)
