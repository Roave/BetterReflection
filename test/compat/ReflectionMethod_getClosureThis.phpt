--TEST--
Reflection::getClosureThis()
--SKIPIF--
<?php
if (!extension_loaded('reflection') || !defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300) {
  print 'skip';
}
?>
--FILE-- 
<?php require 'vendor/autoload.php';
class StaticExample
{
	static function foo()
	{
		var_dump( "Static Example class, Hello World!" );
	}
}

class Example
{
	public $bar = 42;
	public function foo()
	{
		var_dump( "Example class, bar: " . $this->bar );
	}
}

// Initialize classes
$class = \BetterReflection\Reflection\ReflectionClass::createFromName( 'Example' );
$staticclass = \BetterReflection\Reflection\ReflectionClass::createFromName( 'StaticExample' );
$object = new Example();

$method = $staticclass->getMethod( 'foo' );
$closure = $method->getClosure();
$rf = \BetterReflection\Reflection\ReflectionFunction::createFromName($closure);

var_dump($rf->getClosureThis());

$method = $class->getMethod( 'foo' );

$closure = $method->getClosure( $object );
$rf = \BetterReflection\Reflection\ReflectionFunction::createFromName($closure);

var_dump($rf->getClosureThis());

echo "Done!\n";
--EXPECTF--
NULL
object(Example)#%d (1) {
  ["bar"]=>
  int(42)
}
Done!
