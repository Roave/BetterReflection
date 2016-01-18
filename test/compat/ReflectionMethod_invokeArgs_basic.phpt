--TEST--
ReflectionMethod::invokeArgs()
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
}


$testClassInstance = new TestClass();
$testClassInstance->prop = "Hello";

$foo = \BetterReflection\Reflection\ReflectionMethod::createFromName($testClassInstance, 'foo');
$methodWithArgs = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass', 'methodWithArgs');
$methodThatThrows = \BetterReflection\Reflection\ReflectionMethod::createFromName("TestClass::willThrow");


echo "Public method:\n";

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->invokeArgs($testClassInstance, array()));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($foo->invokeArgs($testClassInstance, array(true)));

echo "\nMethod with args:\n";

// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodWithArgs->invokeArgs($testClassInstance, array(1, "arg2")));
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodWithArgs->invokeArgs($testClassInstance, array(1, "arg2", 3)));

echo "\nMethod that throws an exception:\n";
try {
    $methodThatThrows->invokeArgs($testClassInstance, array());
} catch (Exception $e) {
    // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($e->getMessage());
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

Method that throws an exception:
string(18) "Called willThrow()"
