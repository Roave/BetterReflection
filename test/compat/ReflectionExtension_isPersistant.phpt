--TEST--
ReflectionExtension::isPersistent()
--FILE--
<?php require 'vendor/autoload.php';
$obj = \BetterReflection\Reflection\ReflectionExtension::createFromName('reflection');
var_dump($obj->isPersistent());
?>
==DONE==
--EXPECT--
bool(true)
==DONE==
