--TEST--
ReflectionMethod::returnsReference()
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {
    public function &foo() {
    }

    private function bar() {
    }
}

$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass::foo');
var_dump($methodInfo->returnsReference());

$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass::bar');
var_dump($methodInfo->returnsReference());

?>
--EXPECT--
bool(true)
bool(false)
