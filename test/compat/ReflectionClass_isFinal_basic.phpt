--TEST--
ReflectionClass::isFinal() method
--CREDITS--
Felix De Vliegher <felix.devliegher@gmail.com>
#testfest roosendaal on 2008-05-10
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {}
final class TestFinalClass {}

$normalClass = \BetterReflection\Reflection\ReflectionClass::createFromName('TestClass');
$finalClass = \BetterReflection\Reflection\ReflectionClass::createFromName('TestFinalClass');

var_dump($normalClass->isFinal());
var_dump($finalClass->isFinal());

?>
--EXPECT--
bool(false)
bool(true)
