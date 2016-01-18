--TEST--
ReflectionObject::hasProperty
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
class Foo {
	public    $p1;
	protected $p2;
	private   $p3;

	function __isset($name) {
		// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($name);
		return false;
	}
}
$obj = \BetterReflection\Reflection\ReflectionObject::createFromInstance(new Foo());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->hasProperty("p1"));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->hasProperty("p2"));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->hasProperty("p3"));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->hasProperty("p4"));
?>
--EXPECT--	
bool(true)
bool(true)
bool(true)
bool(false)
