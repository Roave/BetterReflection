--TEST--
ReflectionFunction constructor errors
--CREDITS--
Robin Fernandes <robinf@php.net>
Steve Seear <stevseea@php.net>
--FILE--
<?php require 'vendor/autoload.php';

try {
	$a = \BetterReflection\Reflection\ReflectionFunction::createFromName(array(1, 2, 3));
	echo "exception not thrown.".PHP_EOL;
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}
try {
	$a = \BetterReflection\Reflection\ReflectionFunction::createFromName('nonExistentFunction');
} catch (ReflectionException $e) {
	echo $e->getMessage().PHP_EOL;
}
try {
	$a = \BetterReflection\Reflection\ReflectionFunction::createFromName();
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}
try {
	$a = \BetterReflection\Reflection\ReflectionFunction::createFromName(1, 2);
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}
try {
	$a = \BetterReflection\Reflection\ReflectionFunction::createFromName([]);
} catch (TypeError $re) {
	echo "Ok - ".$re->getMessage().PHP_EOL;
}

?>
--EXPECTF--
Ok - ReflectionFunction::__construct() expects parameter 1 to be string, array given
Function nonExistentFunction() does not exist
Ok - ReflectionFunction::__construct() expects exactly 1 parameter, 0 given
Ok - ReflectionFunction::__construct() expects exactly 1 parameter, 2 given
Ok - ReflectionFunction::__construct() expects parameter 1 to be string, array given
