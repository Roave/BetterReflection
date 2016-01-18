--TEST--
Reflection Bug #32981 (ReflectionMethod::getStaticVariables() causes apache2.0.54 seg fault)
--FILE--
<?php require 'vendor/autoload.php';

class TestClass
{
	static function test()
	{
		static $enabled = true;
	}
}

$class = \BetterReflection\Reflection\ReflectionClass::createFromName('TestClass');
foreach ($class->getMethods() as $method)
{
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($method->getName());
	$arr_static_vars[] = $method->getStaticVariables();
}

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($arr_static_vars);

?>
===DONE===
--EXPECT--
string(4) "test"
array(1) {
  [0]=>
  array(1) {
    ["enabled"]=>
    bool(true)
  }
}
===DONE===
