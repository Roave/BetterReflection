--TEST--
ReflectionClass::getName()
--FILE--
<?php
require 'vendor/autoload.php';
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\SingleFileSourceLocator;
$reflector = new ClassReflector(new SingleFileSourceLocator(__FILE__));

class TrickClass {
	function __toString() {
		//Return the name of another class
		return "Exception";
	}
}


$r3 = $reflector->reflect('TrickClass');

var_dump($r3->getName());

?> 
--EXPECTF--
string(10) "TrickClass"
