--TEST--
ReflectionExtension::isTemporary()
--FILE--
<?php require 'vendor/autoload.php';
$obj = \BetterReflection\Reflection\ReflectionExtension::createFromName('reflection');
var_dump($obj->isTemporary());
?>
==DONE==
--EXPECT--
bool(false)
==DONE==
