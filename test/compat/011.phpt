--TEST--
ReflectionExtension::getClasses()
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
$ext = new \BetterReflection\Reflection\ReflectionExtension("reflection");
$classes = $ext->getClasses();
echo $classes["ReflectionException"]->getName();
?>
--EXPECT--	
ReflectionException
