--TEST--
ReflectionClass::getName()
--FILE--
<?php require 'vendor/autoload.php';
class TrickClass {
	function __toString() {
		//Return the name of another class
		return "Exception";
	}
}

$r1 = \BetterReflection\Reflection\ReflectionClass::createFromName('stdClass');

$myInstance = new stdClass;
$r2 = \BetterReflection\Reflection\ReflectionObject::createFromInstance($myInstance);

$r3 = \BetterReflection\Reflection\ReflectionClass::createFromName('TrickClass');

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r1->getName(), $r2->getName(), $r3->getName());

?> 
--EXPECTF--
string(8) "stdClass"
string(8) "stdClass"
string(10) "TrickClass"
