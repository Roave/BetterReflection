--TEST--
ReflectionClass::isInterface() method
--CREDITS--
Felix De Vliegher <felix.devliegher@gmail.com>
#testfest roosendaal on 2008-05-10
--FILE--
<?php require 'vendor/autoload.php';

interface TestInterface {}
class TestClass {}
interface DerivedInterface extends TestInterface {}

$reflectionClass = \BetterReflection\Reflection\ReflectionClass::createFromName('TestInterface');
$reflectionClass2 = \BetterReflection\Reflection\ReflectionClass::createFromName('TestClass');
$reflectionClass3 = \BetterReflection\Reflection\ReflectionClass::createFromName('DerivedInterface');

var_dump($reflectionClass->isInterface());
var_dump($reflectionClass2->isInterface());
var_dump($reflectionClass3->isInterface());

?>
--EXPECT--
bool(true)
bool(false)
bool(true)
