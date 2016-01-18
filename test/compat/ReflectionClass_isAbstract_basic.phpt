--TEST--
ReflectionClass::isAbstract() method
--CREDITS--
Felix De Vliegher <felix.devliegher@gmail.com>
#testfest roosendaal on 2008-05-10
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {}
abstract class TestAbstractClass {}

$testClass = \BetterReflection\Reflection\ReflectionClass::createFromName('TestClass');
$abstractClass = \BetterReflection\Reflection\ReflectionClass::createFromName('TestAbstractClass');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($testClass->isAbstract());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($abstractClass->isAbstract());

?>
--EXPECT--
bool(false)
bool(true)
