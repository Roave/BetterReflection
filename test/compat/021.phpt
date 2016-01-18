--TEST--
ReflectionClass::hasConstant
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
class Foo {
	const c1 = 1;
}
$class = \BetterReflection\Reflection\ReflectionClass::createFromName("Foo");
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($class->hasConstant("c1"));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($class->hasConstant("c2"));
?>
--EXPECT--	
bool(true)
bool(false)
