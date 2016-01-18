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
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($test);
?>
==DONE==
--EXPECT--
bool(true)
==DONE==
