--TEST--
Bug #52057 (ReflectionClass fails on Closure class)
--FILE--
<?php require 'vendor/autoload.php';

$closure = function($a) { echo $a; };

$reflection = \BetterReflection\Reflection\ReflectionClass::createFromName('closure');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($reflection->hasMethod('__invoke')); // true

$reflection = \BetterReflection\Reflection\ReflectionClass::createFromName($closure);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($reflection->hasMethod('__invoke')); // true

$reflection = \BetterReflection\Reflection\ReflectionObject::createFromInstance($closure);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($reflection->hasMethod('__invoke')); // true

$reflection = \BetterReflection\Reflection\ReflectionClass::createFromName('closure');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h = $reflection->getMethod('__invoke')); // true
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h->class.'::'.$h->getName());

$reflection = \BetterReflection\Reflection\ReflectionClass::createFromName($closure);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h = $reflection->getMethod('__invoke')); // true
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h->class.'::'.$h->getName());

$reflection = \BetterReflection\Reflection\ReflectionObject::createFromInstance($closure);
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h = $reflection->getMethod('__invoke')); // true
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($h->class.'::'.$h->getName());

?>
--EXPECTF--
bool(true)
bool(true)
bool(true)
object(ReflectionMethod)#%d (2) {
  ["name"]=>
  string(8) "__invoke"
  ["class"]=>
  string(7) "Closure"
}
string(17) "Closure::__invoke"
object(ReflectionMethod)#%d (2) {
  ["name"]=>
  string(8) "__invoke"
  ["class"]=>
  string(7) "Closure"
}
string(17) "Closure::__invoke"
object(ReflectionMethod)#%d (2) {
  ["name"]=>
  string(8) "__invoke"
  ["class"]=>
  string(7) "Closure"
}
string(17) "Closure::__invoke"
