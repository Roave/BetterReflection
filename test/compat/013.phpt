--TEST--
ReflectionExtension::getFunctions()
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
$ext = \BetterReflection\Reflection\ReflectionExtension::createFromName("standard");
$funcs = $ext->getFunctions();
echo $funcs["sleep"]->getName();
?>
--EXPECT--	
sleep

