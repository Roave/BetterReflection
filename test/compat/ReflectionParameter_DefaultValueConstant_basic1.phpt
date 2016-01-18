--TEST--
ReflectionParameter::isDefaultValueConstant() && getDefaultValueConstantName()
--FILE--
<?php require 'vendor/autoload.php';

define("CONST_TEST_1", "const1");

function ReflectionParameterTest($test1=array(), $test2 = CONST_TEST_1, $test3 = CASE_LOWER) {
	echo $test;
}
$reflect = \BetterReflection\Reflection\ReflectionFunction::createFromName('ReflectionParameterTest');
foreach($reflect->getParameters() as $param) {
	if($param->getName() == 'test1') {
		// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($param->isDefaultValueConstant());
	}
	if($param->getName() == 'test2') {
		// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($param->isDefaultValueConstant());
	}
	if($param->isDefaultValueAvailable() && $param->isDefaultValueConstant()) {
		// @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($param->getDefaultValueConstantName());
	}
}

class Foo2 {
	const bar = 'Foo2::bar';
}

class Foo {
	const bar = 'Foo::bar';

	public function baz($param1 = self::bar, $param2=Foo2::bar, $param3=CONST_TEST_1) {
	}
}

$method = \BetterReflection\Reflection\ReflectionMethod::createFromName('Foo', 'baz');
$params = $method->getParameters();

foreach ($params as $param) {
    if ($param->isDefaultValueConstant()) {
        // @todo see https://github.com/Roave/BetterReflection/issues/155 --- var_dump($param->getDefaultValueConstantName());
    }
}
?>
==DONE==
--EXPECT--
bool(false)
bool(true)
string(12) "CONST_TEST_1"
string(10) "CASE_LOWER"
string(9) "self::bar"
string(9) "Foo2::bar"
string(12) "CONST_TEST_1"
==DONE==
