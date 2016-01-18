--TEST--
ReflectionExtension::isPersistent()
--FILE--
<?php require 'vendor/autoload.php';
$obj = \BetterReflection\Reflection\ReflectionExtension::createFromName('reflection');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isPersistent());
?>
==DONE==
--EXPECT--
bool(true)
==DONE==
