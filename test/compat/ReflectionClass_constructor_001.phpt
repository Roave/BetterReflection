--TEST--
ReflectionClass::__constructor()
--FILE--
<?php require 'vendor/autoload.php';
$r1 = \BetterReflection\Reflection\ReflectionClass::createFromName("stdClass");

$myInstance = new stdClass;
$r2 = \BetterReflection\Reflection\ReflectionObject::createFromInstance($myInstance);

class TrickClass {
	function __toString() {
		//Return the name of another class
		return "Exception";
	}
}
$myTrickClass = new TrickClass;
$r3 = \BetterReflection\Reflection\ReflectionObject::createFromInstance($myTrickClass);

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($r1->getName(), $r2->getName(), $r3->getName());
?>
--EXPECTF--
string(8) "stdClass"
string(8) "stdClass"
string(10) "TrickClass"
