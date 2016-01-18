--TEST--
ReflectionClass::isAnonymous() method
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {}

$declaredClass = \BetterReflection\Reflection\ReflectionClass::createFromName('TestClass');
$anonymousClass = \BetterReflection\Reflection\ReflectionClass::createFromName(new class {});

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($declaredClass->isAnonymous());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($anonymousClass->isAnonymous());

?>
--EXPECT--
bool(false)
bool(true)
