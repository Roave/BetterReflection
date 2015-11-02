--TEST--
ReflectionClass::__constructor() - bad arguments
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';
try {
	var_dump(\BetterReflection\Reflection\ReflectionClass::createFromName());
} catch (Exception $e) {
	echo $e->getMessage() . "\n";  
}

try {
	var_dump(\BetterReflection\Reflection\ReflectionClass::createFromName(null));
} catch (Exception $e) {
	echo $e->getMessage() . "\n";  
}

try {
	var_dump(\BetterReflection\Reflection\ReflectionClass::createFromName(true));
} catch (Exception $e) {
	echo $e->getMessage() . "\n";  
}

try {
	var_dump(\BetterReflection\Reflection\ReflectionClass::createFromName(1));
} catch (Exception $e) {
	echo $e->getMessage() . "\n";  
}

try {
	var_dump(\BetterReflection\Reflection\ReflectionClass::createFromName(array(1,2,3)));
} catch (Exception $e) {
	echo $e->getMessage() . "\n";  
}

try {
	var_dump(\BetterReflection\Reflection\ReflectionClass::createFromName("stdClass", 1));
} catch (Exception $e) {
	echo $e->getMessage() . "\n";  
}

try {
	var_dump(\BetterReflection\Reflection\ReflectionClass::createFromName("X"));
} catch (Exception $e) {
	echo $e->getMessage() . "\n";  
}

?>
--EXPECTF--

Warning: ReflectionClass::__construct() expects exactly 1 parameter, 0 given in %s on line 3
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(0) ""
}
Class  does not exist
Class 1 does not exist
Class 1 does not exist

Notice: Array to string conversion in %s on line 27
Class Array does not exist

Warning: ReflectionClass::__construct() expects exactly 1 parameter, 2 given in %s on line 33
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(0) ""
}
Class X does not exist
