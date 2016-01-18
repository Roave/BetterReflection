--TEST--
Bug #41884 (ReflectionClass::getDefaultProperties() does not handle static attributes)
--FILE--
<?php require 'vendor/autoload.php';

class Foo
{
	protected static $fooStatic = 'foo';
	protected $foo = 'foo';
}

$class = \BetterReflection\Reflection\ReflectionClass::createFromName('Foo');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($class->getDefaultProperties());

echo "Done\n";
?>
--EXPECTF--	
array(2) {
  ["fooStatic"]=>
  string(3) "foo"
  ["foo"]=>
  string(3) "foo"
}
Done
