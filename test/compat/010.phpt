--TEST--
ReflectionMethod::__toString() tests (overriden method)
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
class Foo {
	function func() {
	}
}
class Bar extends Foo {
	function func() {
	}
}
$m = new \BetterReflection\Reflection\ReflectionMethod("Bar::func");
echo $m;
?>
--EXPECTF--	
Method [ <user, overwrites Foo, prototype Foo> public method func ] {
  @@ %s010.php 7 - 8
}
