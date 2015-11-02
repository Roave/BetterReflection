--TEST--
Test ReflectionProperty::getDeclaringClass() with inherited properties.
--FILE--
<?php require 'vendor/autoload.php';

class A {
    public $prop;
}

class B extends A {
}

$propInfo = \BetterReflection\Reflection\ReflectionProperty::createFromName('B', 'prop');
var_dump($propInfo->getDeclaringClass());

echo "Wrong number of params:\n";
$propInfo->getDeclaringClass(1);

?> 
--EXPECTF--
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(1) "A"
}
Wrong number of params:

Warning: ReflectionProperty::getDeclaringClass() expects exactly 0 parameters, 1 given in %s on line %d
