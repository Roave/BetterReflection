--TEST--
ReflectionMethod::invoke()
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {
    public $prop = 2;

    public function foo() {
        echo "Called foo(), property = $this->prop\n";
        // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($this);
        return "Return Val";
    }

    public function willThrow() {
        throw new Exception("Called willThrow()");
    }

    public function methodWithArgs($a, $b) {
        echo "Called methodWithArgs($a, $b)\n";
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

$foo = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass', 'foo');
$methodWithArgs = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass', 'methodWithArgs');
$staticMethod = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass::staticMethod');
$privateMethod = \BetterReflection\Reflection\ReflectionMethod::createFromName("TestClass::privateMethod");
$methodThatThrows = \BetterReflection\Reflection\ReflectionMethod::createFromName("TestClass::willThrow");

$testClassInstance = new TestClass();
$testClassInstance->prop = "Hello";

echo "Public method:\n";

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->invoke($testClassInstance));

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->invoke($testClassInstance, true));

echo "\nMethod with args:\n";

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodWithArgs->invoke($testClassInstance, 1, "arg2"));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodWithArgs->invoke($testClassInstance, 1, "arg2", 3));

echo "\nStatic method:\n";

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($staticMethod->invoke());
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($staticMethod->invoke(true));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($staticMethod->invoke(new stdClass()));

echo "\nMethod that throws an exception:\n";
try {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodThatThrows->invoke($testClassInstance));
} catch (Exception $exc) {
	// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($exc->getMessage());
}

?>
--EXPECTF--
Public method:
Called foo(), property = Hello
object(TestClass)#%d (1) {
  ["prop"]=>
  string(5) "Hello"
}
string(10) "Return Val"
Called foo(), property = Hello
object(TestClass)#%d (1) {
  ["prop"]=>
  string(5) "Hello"
}
string(10) "Return Val"

Method with args:
Called methodWithArgs(1, arg2)
NULL
Called methodWithArgs(1, arg2)
NULL

Static method:

Warning: ReflectionMethod::invoke() expects at least 1 parameter, 0 given in %s on line %d
NULL
Called staticMethod()

Notice: Undefined variable: this in %s on line %d
NULL
NULL
Called staticMethod()

Notice: Undefined variable: this in %s on line %d
NULL
NULL

Method that throws an exception:
string(18) "Called willThrow()"
