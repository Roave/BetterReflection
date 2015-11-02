--TEST--
ReflectionExtension::getVersion()
--CREDITS--
Gerrit "Remi" te Sligte <remi@wolerized.com>
Leon Luijkx <leon@phpgg.nl>
--FILE--
<?php require 'vendor/autoload.php';
$obj = \BetterReflection\Reflection\ReflectionExtension::createFromName('reflection');
$var = $obj->getVersion() ? $obj->getVersion() : null;
$test = floatval($var) == $var ? true : false;
var_dump($test);
?>
==DONE==
--EXPECT--
bool(true)
==DONE==
