--TEST--
Reflection Bug #30209 (ReflectionClass::getMethod() lowercases attribute)
--FILE--
<?php require 'vendor/autoload.php';

class Foo
{
	private $name = 'testBAR';

	public function testBAR()
	{
		try
		{
			$class  = \BetterReflection\Reflection\ReflectionClass::createFromName($this);
			// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($this->name);
			$method = $class->getMethod($this->name);
			// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($this->name);
		}

		catch (Exception $e) {}
	}
}

$foo = new Foo;
$foo->testBAR();
?>
===DONE===
--EXPECTF--
string(7) "testBAR"
string(7) "testBAR"
===DONE===
