--TEST--
ReflectionClass::getName()
--FILE--
<?php
require 'vendor/autoload.php';

class TrickClass {
	function __toString() {
		//Return the name of another class
		return "Exception";
	}
}


$r3 = (new \BetterReflection\Reflector\ClassReflector(
    new \BetterReflection\SourceLocator\SingleFileSourceLocator(__FILE__)
))->reflect('TrickClass');

var_dump($r3->getName());

?> 
--EXPECTF--
string(10) "TrickClass"
