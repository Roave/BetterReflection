--TEST--
ReflectionFunction::isDeprecated
--CREDITS--
Stefan Koopmanschap <stefan@phpgg.nl>
TestFest PHP|Tek
--FILE-- 
<?php require 'vendor/autoload.php';
// We currently don't have any deprecated functions :/
$rc = \BetterReflection\Reflection\ReflectionFunction::createFromName('var_dump');
var_dump($rc->isDeprecated());
--EXPECTF--
bool(false)
