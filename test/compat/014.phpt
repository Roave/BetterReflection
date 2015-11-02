--TEST--
ReflectionExtension::getConstants()
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
$ext = \BetterReflection\Reflection\ReflectionExtension::createFromName("standard");
$consts = $ext->getConstants();
var_dump($consts["CONNECTION_NORMAL"]);
?>
--EXPECT--	
int(0)

