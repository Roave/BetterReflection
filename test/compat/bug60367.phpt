--TEST--
Bug #60367 (Reflection and Late Static Binding)
--FILE--
<?php require 'vendor/autoload.php';
abstract class A {

	const WHAT = 'A';

	public static function call() {
		echo static::WHAT;
	}

}

class B extends A {

	const WHAT = 'B';

}

$method = \BetterReflection\Reflection\ReflectionMethod::createFromName("b::call");
$method->invoke(null);
$method->invokeArgs(null, array());
$method = \BetterReflection\Reflection\ReflectionMethod::createFromName("A::call");
$method->invoke(null);
$method->invokeArgs(null, array());
--EXPECTF--
BBAA
