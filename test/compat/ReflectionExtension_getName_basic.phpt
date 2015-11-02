--TEST--
ReflectionExtension::getName()
--CREDITS--
Gerrit "Remi" te Sligte <remi@wolerized.com>
Leon Luijkx <leon@phpgg.nl>
--FILE--
<?php require 'vendor/autoload.php';
$obj = \BetterReflection\Reflection\ReflectionExtension::createFromName('reflection');
var_dump($obj->getName());
?>
==DONE==
--EXPECT--
string(10) "Reflection"
==DONE==
