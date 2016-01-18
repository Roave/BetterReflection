--TEST--
ReflectionFunction::isDeprecated
--CREDITS--
Stefan Koopmanschap <stefan@phpgg.nl>
TestFest PHP|Tek
--FILE-- 
<?php require 'vendor/autoload.php';
// We currently don't have any deprecated functions :/
$rc = \BetterReflection\Reflection\ReflectionFunction::createFromName('// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->isDeprecated());
--EXPECTF--
bool(false)
