--TEST--
ReflectionMethod::invoke() with base class method
--FILE--
<?php require 'vendor/autoload.php';

class Foo
{
	function Test()
	{
		echo __METHOD__ . "\n";
	}
}

class Bar extends Foo
{
	function Test()
	{
		echo __METHOD__ . "\n";
	}
}

$o = new Bar;
$r = new \BetterReflection\Reflection\ReflectionMethod('Foo','Test');

$r->invoke($o);

?>
===DONE===
--EXPECT--
Foo::Test
===DONE===
