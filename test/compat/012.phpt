--TEST--
ReflectionClass::getDefaultProperties()
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
class Foo {
	public $test = "ok";
}
$class = \BetterReflection\Reflection\ReflectionClass::createFromName("Foo");
$props = $class->getDefaultProperties();
echo $props["test"];
?>
--EXPECT--	
ok

