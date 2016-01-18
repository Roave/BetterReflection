--TEST--
Bug #38217 (ReflectionClass::newInstanceArgs() tries to allocate too much memory)
--FILE--
<?php require 'vendor/autoload.php';

class Object {
	public function __construct() {
	}
}

$class= \BetterReflection\Reflection\ReflectionClass::createFromName('Object');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($class->newInstanceArgs());

class Object1 {
	public function __construct($var) {
		// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($var);
	}
}

$class= \BetterReflection\Reflection\ReflectionClass::createFromName('Object1');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($class->newInstanceArgs());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($class->newInstanceArgs(array('test')));


echo "Done\n";
?>
--EXPECTF--	
object(Object)#%d (0) {
}

Warning: Missing argument 1 for Object1::__construct() in %s on line %d

Notice: Undefined variable: var in %s on line %d
NULL
object(Object1)#%d (0) {
}
string(4) "test"
object(Object1)#%d (0) {
}
Done
