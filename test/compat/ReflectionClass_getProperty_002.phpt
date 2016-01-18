--TEST--
ReflectionClass::getProperty() - error cases
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--SKIPIF--
skip
<?php
// Skipping this as too slow currently :(
// see https://github.com/Roave/BetterReflection/issues/146
--FILE--
<?php require 'vendor/autoload.php';
class C {
	public $a;
}

$rc = \BetterReflection\Reflection\ReflectionClass::createFromName("C");
echo "Check invalid params:\n";
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getProperty());
} catch (exception $e) {
	echo $e->getMessage() . "\n";	
}
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getProperty("a", "a"));
} catch (exception $e) {
	echo $e->getMessage() . "\n";	
}
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getProperty(null));
} catch (exception $e) {
	echo $e->getMessage() . "\n";	
}
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getProperty(1));
} catch (exception $e) {
	echo $e->getMessage() . "\n";	
}
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getProperty(1.5));
} catch (exception $e) {
	echo $e->getMessage() . "\n";	
}
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getProperty(true));
} catch (exception $e) {
	echo $e->getMessage() . "\n";	
}
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getProperty(array(1,2,3)));
} catch (exception $e) {
	echo $e->getMessage() . "\n";	
}
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($rc->getProperty(new C));
} catch (exception $e) {
	echo $e->getMessage() . "\n";	
}
?>
--EXPECTF--
Check invalid params:

Warning: ReflectionClass::getProperty() expects exactly 1 parameter, 0 given in %s on line 9
NULL

Warning: ReflectionClass::getProperty() expects exactly 1 parameter, 2 given in %s on line 14
NULL
Property  does not exist
Property 1 does not exist
Property 1.5 does not exist
Property 1 does not exist

Warning: ReflectionClass::getProperty() expects parameter 1 to be string, array given in %s on line 39
NULL

Warning: ReflectionClass::getProperty() expects parameter 1 to be string, object given in %s on line 44
NULL
