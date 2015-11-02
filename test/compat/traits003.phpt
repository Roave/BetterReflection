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
var_dump($x->isTrait());

$x = \BetterReflection\Reflection\ReflectionClass::createFromName('bar');
var_dump($x->isTrait());

$x = \BetterReflection\Reflection\ReflectionClass::createFromName('baz');
var_dump($x->isTrait());

?>
--EXPECT--
bool(false)
bool(true)
bool(false)
