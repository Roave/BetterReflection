--TEST--
ReflectionParameter::__construct(): Invalid method as constructor
--FILE-- 
<?php require 'vendor/autoload.php';

// Invalid class name
try {
	\BetterReflection\Reflection\ReflectionParameter::createFromName (array ('A', 'b'), 0);
} catch (ReflectionException $e) { echo $e->getMessage()."\n"; }

// Invalid class method
try {
	\BetterReflection\Reflection\ReflectionParameter::createFromName (array ('C', 'b'), 0);
} catch (ReflectionException $e) { echo $e->getMessage ()."\n"; }

// Invalid object method
try {
	\BetterReflection\Reflection\ReflectionParameter::createFromName (array (new C, 'b'), 0);
} catch (ReflectionException $e) { echo $e->getMessage ()."\n"; }


class C {
}

try {
	\BetterReflection\Reflection\ReflectionParameter::createFromName(array ('A', 'b'));
}
catch(TypeError $e) {
	printf( "Ok - %s\n", $e->getMessage());
}

try {
	\BetterReflection\Reflection\ReflectionParameter::createFromName(0, 0);
}
catch(ReflectionException $e) {
	printf( "Ok - %s\n", $e->getMessage());
}

echo "Done.\n";

?>
--EXPECTF--
Class A does not exist
Method C::b() does not exist
Method C::b() does not exist
Ok - ReflectionParameter::__construct() expects exactly 2 parameters, 1 given
Ok - The parameter class is expected to be either a string, an array(class, method) or a callable object
Done.
