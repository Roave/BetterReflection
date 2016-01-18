--TEST--
ReflectionExtension::isTemporary()
--FILE--
<?php require 'vendor/autoload.php';
$obj = \BetterReflection\Reflection\ReflectionExtension::createFromName('reflection');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($obj->isTemporary());
?>
==DONE==
--EXPECT--
bool(false)
==DONE==
