--TEST--
Bug #67068 (ReflectionFunction::getClosure returns something that doesn't report as a closure)
--FILE--
<?php require 'vendor/autoload.php';
class MyClass {
    public function method() {}
}

$object = new MyClass;
$reflector = new \ReflectionMethod($object, 'method');
$closure = $reflector->getClosure($object);

$closureReflector = new \ReflectionFunction($closure);

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($closureReflector->isClosure());
?>
--EXPECT--
bool(true)
