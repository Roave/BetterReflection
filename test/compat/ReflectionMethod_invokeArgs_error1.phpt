--TEST--
ReflectionMethod:invokeArgs() errors
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {

    public function methodWithArgs($a, $b) {
        echo "Called methodWithArgs($a, $b)\n";
    }
}

abstract class AbstractClass {
    abstract function foo();
}

$methodWithArgs = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass', 'methodWithArgs');

$testClassInstance = new TestClass();

echo "\nMethod with args:\n";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodWithArgs->invokeArgs($testClassInstance, array()));

?>
--EXPECTF--
Method with args:

Warning: Missing argument 1 for TestClass::methodWithArgs() in %s on line %d

Warning: Missing argument 2 for TestClass::methodWithArgs() in %s on line %d

Notice: Undefined variable: a in %s on line %d

Notice: Undefined variable: b in %s on line %d
Called methodWithArgs(, )
NULL
