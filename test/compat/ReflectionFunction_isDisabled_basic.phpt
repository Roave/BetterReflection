--TEST--
ReflectionFunction::isDisabled
--CREDITS--
Stefan Koopmanschap <stefan@phpgg.nl>
TestFest PHP|Tek
--SKIPIF--
<?php
if (!extension_loaded('reflection')) print 'skip';
?>
--INI--
disable_functions=is_file
--FILE-- 
<?php require 'vendor/autoload.php';
$rc = \BetterReflection\Reflection\ReflectionFunction::createFromName('is_file');
var_dump($rc->isDisabled());
--EXPECTF--
bool(true)
