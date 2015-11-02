--TEST--
ReflectionFunction::getExtensionName
--SKIPIF--
<?php extension_loaded('reflection') or die('skip'); ?>
--FILE--
<?php require 'vendor/autoload.php';
$f = \BetterReflection\Reflection\ReflectionFunction::createFromName("sleep");
var_dump($f->getExtensionName());
?>
--EXPECT--	
string(8) "standard"
