--TEST--
ReflectionMethod::invokeArgs() further errors
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {
    public $prop = 2;

    public function foo() {
        echo "Called foo(), property = $this->prop\n";
        // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($this);
        return "Return Val";
    }

    public static function staticMethod() {
        echo "Called staticMethod()\n";
        // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($this);
    }

    private static function privateMethod() {
        echo "Called privateMethod()\n";
    }
}

abstract class AbstractClass {
    abstract function foo();
}

$testClassInstance = new TestClass();
$testClassInstance->prop = "Hello";

$foo = \BetterReflection\Reflection\ReflectionMethod::createFromName($testClassInstance, 'foo');
$staticMethod = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass::staticMethod');
$privateMethod = \BetterReflection\Reflection\ReflectionMethod::createFromName("TestClass::privateMethod");

echo "Wrong number of parameters:\n";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->invokeArgs());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->invokeArgs(true));

echo "\nNon-instance:\n";
try {
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->invokeArgs(new stdClass(), array()));
} catch (ReflectionException $e) {
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e->getMessage());
}

echo "\nNon-object:\n";
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->invokeArgs(true, array()));

echo "\nStatic method:\n";

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($staticMethod->invokeArgs());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($staticMethod->invokeArgs(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($staticMethod->invokeArgs(true, array()));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($staticMethod->invokeArgs(null, array()));

echo "\nPrivate method:\n";
try {
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($privateMethod->invokeArgs($testClassInstance, array()));
} catch (ReflectionException $e) {
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e->getMessage());
}

echo "\nAbstract method:\n";
$abstractMethod = \BetterReflection\Reflection\ReflectionMethod::createFromName("AbstractClass::foo");
try {
    $abstractMethod->invokeArgs($testClassInstance, array());
} catch (ReflectionException $e) {
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e->getMessage());
}
try {
    $abstractMethod->invokeArgs(true);
} catch (ReflectionException $e) {
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e->getMessage());
}

?>
--EXPECTF--
Wrong number of parameters:

Warning: ReflectionMethod::invokeArgs() expects exactly 2 parameters, 0 given in %s on line %d
NULL

Warning: ReflectionMethod::invokeArgs() expects exactly 2 parameters, 1 given in %s on line %d
NULL

Non-instance:
string(72) "Given object is not an instance of the class this method was declared in"

Non-object:

Warning: ReflectionMethod::invokeArgs() expects parameter 1 to be object, boolean given in %s on line %d
NULL

Static method:

Warning: ReflectionMethod::invokeArgs() expects exactly 2 parameters, 0 given in %s on line %d
NULL

Warning: ReflectionMethod::invokeArgs() expects exactly 2 parameters, 1 given in %s on line %d
NULL

Warning: ReflectionMethod::invokeArgs() expects parameter 1 to be object, boolean given in %s on line %d
NULL
Called staticMethod()

Notice: Undefined variable: this in %s on line %d
NULL
NULL

Private method:
string(86) "Trying to invoke private method TestClass::privateMethod() from scope ReflectionMethod"

Abstract method:
string(53) "Trying to invoke abstract method AbstractClass::foo()"

Warning: ReflectionMethod::invokeArgs() expects exactly 2 parameters, 1 given in %s on line %d
