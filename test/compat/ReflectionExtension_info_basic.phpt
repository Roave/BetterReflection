--TEST--
ReflectionExtension::info()
--CREDITS--
Gerrit "Remi" te Sligte <remi@wolerized.com>
Leon Luijkx <leon@phpgg.nl>
--FILE--
<?php require 'vendor/autoload.php';
$obj = \BetterReflection\Reflection\ReflectionExtension::createFromName('reflection');
ob_start();
$testa = $obj->info();
$testb = ob_get_clean();
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($testa);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(strlen($testb) > 24);
?>
==DONE==
--EXPECT--
NULL
bool(true)
==DONE==
