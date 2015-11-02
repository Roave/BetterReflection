--TEST--
ReflectionClass::isAnonymous() method
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {}

$declaredClass = \BetterReflection\Reflection\ReflectionClass::createFromName('TestClass');
$anonymousClass = \BetterReflection\Reflection\ReflectionClass::createFromName(new class {});

var_dump($declaredClass->isAnonymous());
var_dump($anonymousClass->isAnonymous());

?>
--EXPECT--
bool(false)
bool(true)
