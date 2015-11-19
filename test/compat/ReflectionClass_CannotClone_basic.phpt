--TEST--
Reflection class can not be cloned
--CREDITS--
Stefan Koopmanschap <stefan@phpgg.nl>
TestFest PHP|Tek
--SKIPIF--
<?php
if (!extension_loaded('reflection')) print 'skip';
?>
--FILE-- 
<?php require 'vendor/autoload.php';
$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("stdClass");
$rc2 = clone($rc);
--EXPECTF--
Fatal error: Uncaught exception 'BetterReflection\Reflection\Exception\Uncloneable' with message 'Trying to clone an uncloneable object of class BetterReflection\Reflection\ReflectionClass' in %s:%d
Stack trace:
#0 -(3): BetterReflection\Reflection\ReflectionClass->__clone()
#1 {main}
  thrown in %s/ReflectionClass.php on line %d
