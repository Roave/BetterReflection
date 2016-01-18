--TEST--
ReflectionMethod::getDeclaringClass()
--FILE--
<?php require 'vendor/autoload.php';

class A {
    function foo() {}
}

class B extends A {
    function bar() {}
}

$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName('B', 'foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodInfo->getDeclaringClass());

$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName('B', 'bar');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodInfo->getDeclaringClass());

?> 
--EXPECTF--
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(1) "A"
}
object(ReflectionClass)#%d (1) {
  ["name"]=>
  string(1) "B"
}

