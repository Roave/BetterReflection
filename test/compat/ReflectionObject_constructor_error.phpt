--TEST--
ReflectionObject::__construct - invalid arguments
--FILE--
<?php require 'vendor/autoload.php';

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(\BetterReflection\Reflection\ReflectionObject::createFromInstance());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(\BetterReflection\Reflection\ReflectionObject::createFromInstance('stdClass'));
$myInstance = new stdClass;
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(\BetterReflection\Reflection\ReflectionObject::createFromInstance($myInstance, $myInstance));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(\BetterReflection\Reflection\ReflectionObject::createFromInstance(0));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(\BetterReflection\Reflection\ReflectionObject::createFromInstance(null));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump(\BetterReflection\Reflection\ReflectionObject::createFromInstance(array(1,2)));
?>
--EXPECTF--
Warning: ReflectionObject::__construct() expects exactly 1 parameter, 0 given in %s on line 3
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(0) ""
}

Warning: ReflectionObject::__construct() expects parameter 1 to be object, string given in %s on line 4
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(0) ""
}

Warning: ReflectionObject::__construct() expects exactly 1 parameter, 2 given in %s on line 6
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(0) ""
}

Warning: ReflectionObject::__construct() expects parameter 1 to be object, integer given in %s on line 7
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(0) ""
}

Warning: ReflectionObject::__construct() expects parameter 1 to be object, null given in %s on line 8
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(0) ""
}

Warning: ReflectionObject::__construct() expects parameter 1 to be object, array given in %s on line 9
object(ReflectionObject)#%d (1) {
  ["name"]=>
  string(0) ""
}
