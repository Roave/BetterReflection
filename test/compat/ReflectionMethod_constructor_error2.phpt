--TEST--
ReflectionMethod constructor errors
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';

class TestClass
{
    public function foo() {
    }
}


try {
	echo "Too few arguments:\n";
	$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName();
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}
try {
	echo "\nToo many arguments:\n";
	$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("TestClass", "foo", true);
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}


try {
	//invalid class
	$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("InvalidClassName", "foo");
} catch (ReflectionException $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}


try {
	//invalid 1st param
	$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName([], "foo");
} catch (ReflectionException $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}

try{
	//invalid 2nd param
	$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName("TestClass", []);
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}

?>
--EXPECTF--
Too few arguments:
Ok - ReflectionMethod::__construct() expects exactly 1 parameter, 0 given

Too many arguments:
Ok - ReflectionMethod::__construct() expects exactly 1 parameter, 3 given
Ok - Class InvalidClassName does not exist
Ok - The parameter class is expected to be either a string or an object
Ok - ReflectionMethod::__construct() expects exactly 1 parameter, 2 given

