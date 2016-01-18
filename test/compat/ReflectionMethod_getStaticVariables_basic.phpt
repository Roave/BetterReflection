--TEST--
ReflectionMethod::getStaticVariables()
--FILE--
<?php require 'vendor/autoload.php';

class TestClass {
    public function foo() {
        static $c;
        static $a = 1;
        static $b = "hello";
        $d = 5;
    }

    private function bar() {
        static $a = 1;
    }

    public function noStatics() {
        $a = 54;
    }
}

echo "Public method:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass::foo');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodInfo->getStaticVariables());

echo "\nPrivate method:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass::bar');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodInfo->getStaticVariables());

echo "\nMethod with no static variables:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName('TestClass::noStatics');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodInfo->getStaticVariables());

echo "\nInternal Method:\n";
$methodInfo = \BetterReflection\Reflection\ReflectionMethod::createFromName('ReflectionClass::getName');
// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($methodInfo->getStaticVariables());

?>
--EXPECT--
Public method:
array(3) {
  ["c"]=>
  NULL
  ["a"]=>
  int(1)
  ["b"]=>
  string(5) "hello"
}

Private method:
array(1) {
  ["a"]=>
  int(1)
}

Method with no static variables:
array(0) {
}

Internal Method:
array(0) {
}
